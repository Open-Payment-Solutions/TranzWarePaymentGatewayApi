require 'yaml'

errors = []
workflows = Dir['.github/workflows/*.{yml,yaml}'].to_h do |path|
  workflow = YAML.safe_load(File.read(path))
  events = workflow['on'] || workflow[true]
  errors << "#{path}: unexpected workflow trigger" unless events.is_a?(Hash) &&
    (events.keys - %w[push pull_request]).empty?
  errors << "#{path}: default token must be read-only" unless workflow['permissions'] == { 'contents' => 'read' }

  workflow.fetch('jobs').each do |name, job|
    location = "#{path}:#{name}"
    expected = path.end_with?('/release.yml') && name == 'publish' ? 'write' : 'read'
    permissions = job.fetch('permissions', workflow['permissions'])
    errors << "#{location}: excessive token permissions" unless permissions == { 'contents' => expected }
    errors << "#{location}: use an isolated GitHub-hosted runner" unless job['runs-on'] == 'ubuntu-latest'

    job.fetch('steps').each do |step|
      if step['uses']
        errors << "#{location}: action must use a full commit SHA" unless step['uses'].match?(%r{\A[^@]+@[0-9a-f]{40}\z})
        if step['uses'].start_with?('actions/checkout@') && step.dig('with', 'persist-credentials') != false
          errors << "#{location}: checkout must not persist credentials"
        end
      end
      if step['run']&.include?('composer install') &&
          !%w[--no-plugins --no-scripts].all? { |flag| step['run'].include?(flag) }
        errors << "#{location}: disable dependency install hooks"
      end
      step.fetch('env', {}).each_value do |value|
        if value.to_s.include?('secrets.') &&
            !(expected == 'write' && value == '${{ secrets.GITHUB_TOKEN }}')
          errors << "#{location}: unexpected secret access"
        end
      end
    end
  end
  [File.basename(path), workflow]
end

ci = workflows.fetch('php.yml')
errors << 'PHP CI must use pull_request for untrusted contributions' unless (ci['on'] || ci[true]).key?('pull_request')
release = workflows.fetch('release.yml')
events = release['on'] || release[true]
errors << 'Release workflow must only run on version tag pushes' unless events == {
  'push' => { 'tags' => ['[0-9]*.[0-9]*.[0-9]*'] }
}
jobs = release.fetch('jobs')
authorize = jobs.fetch('authorize')
errors << 'Release authorization must require a tag push' unless authorize['if'] == "github.event_name == 'push' && github.ref_type == 'tag'"
source = authorize.fetch('steps').find { |step| step['id'] == 'source' }
unless authorize.fetch('steps').first.dig('with', 'ref') == 'refs/heads/main' &&
    authorize.fetch('steps').first.dig('with', 'fetch-depth') == 0 &&
    source&.fetch('run', '')&.include?('git merge-base --is-ancestor "$GITHUB_SHA" HEAD')
  errors << 'Release authorization must verify tag ancestry against main'
end
errors << 'Release validation must require authorization' unless jobs.fetch('validate')['needs'] == 'authorize'
publish = jobs.fetch('publish')
errors << 'Publishing must require authorization and validation' unless publish['needs'].sort == %w[authorize validate]
steps = publish.fetch('steps')
unless steps.length == 3 && steps[0].dig('with', 'ref') == '${{ needs.authorize.outputs.trusted-sha }}' &&
    steps[0].dig('with', 'path') == 'release-tools' &&
    steps[0].dig('with', 'sparse-checkout') == '.github/scripts/publish-release.py' &&
    steps[1].dig('with', 'ref') == '${{ needs.authorize.outputs.release-sha }}' &&
    steps[1].dig('with', 'path') == 'release-data' &&
    steps[1].dig('with', 'sparse-checkout').to_s.lines.map(&:strip) == %w[/VERSION /CHANGELOG.md] &&
    steps[2]['working-directory'] == 'release-data' &&
    steps[2]['run'] == 'python3 -I "$GITHUB_WORKSPACE/release-tools/.github/scripts/publish-release.py"' &&
    steps[2].dig('env', 'TRUSTED_MAIN_SHA') == '${{ needs.authorize.outputs.trusted-sha }}'
  errors << 'Publishing must execute trusted tooling and consume only validated release metadata'
end

abort errors.join("\n") unless errors.empty?
puts 'Workflow security checks passed'
