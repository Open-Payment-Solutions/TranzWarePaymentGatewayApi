import json
import os
import re
import urllib.error
import urllib.request
from pathlib import Path


def request(method, path, data=None, allow_missing=False):
    repository = os.environ['GITHUB_REPOSITORY']
    token = os.environ['GITHUB_TOKEN']
    url = f'https://api.github.com/repos/{repository}{path}'
    call = urllib.request.Request(
        url,
        data=json.dumps(data).encode() if data is not None else None,
        method=method,
        headers={
            'Authorization': f'Bearer {token}',
            'Accept': 'application/vnd.github+json',
            'X-GitHub-Api-Version': '2022-11-28',
            'User-Agent': 'TranzWare-release',
            'Content-Type': 'application/json',
        },
    )
    try:
        with urllib.request.urlopen(call, timeout=30) as response:
            body = response.read()
            return json.loads(body) if body else None
    except urllib.error.HTTPError as error:
        if allow_missing and error.code == 404:
            return None
        raise RuntimeError(f'GitHub release request failed: {method} {path} (HTTP {error.code})') from None


def release_notes(version):
    changelog = Path('CHANGELOG.md').read_text()
    match = re.search(rf'^## {re.escape(version)}\n(.*?)(?=^## |\Z)', changelog, re.M | re.S)
    if not match:
        raise RuntimeError(f'Missing changelog entry for {version}')
    repository = os.environ['GITHUB_REPOSITORY']
    notes = (
        '> [!WARNING]\n'
        '> Versions after 1.2.3 contain breaking changes. Existing integrations '
        'should pin to exactly `1.2.3` until migrated and tested.\n\n'
        f'{match.group(1).strip()}\n\n'
    )
    if version == '1.5.1':
        data_changes = re.search(r'^## 1\.5\.0\n(.*?)(?=^## |\Z)', changelog, re.M | re.S)
        if data_changes:
            notes += f'## Included API updates from 1.5.0\n\n{data_changes.group(1).strip()}\n\n'
    notes += (
        f'[Integration guide](https://github.com/{repository}/blob/{version}/docs/README.md) '
        f'| [Full changelog](https://github.com/{repository}/blob/{version}/CHANGELOG.md)\n'
    )
    return notes


def main():
    version = os.environ['GITHUB_REF_NAME']
    if not re.fullmatch(r'\d+\.\d+\.\d+', version) or Path('VERSION').read_text().strip() != version:
        raise RuntimeError('Release tag must match the version in VERSION')
    body = release_notes(version)
    release = request('GET', f'/releases/tags/{version}', allow_missing=True)
    if release is None:
        release = request('POST', '/releases', {
            'tag_name': version,
            'target_commitish': os.environ['GITHUB_SHA'],
            'name': version,
            'body': body,
            'draft': False,
            'prerelease': False,
            'make_latest': 'true',
        })
    verified = request('GET', f'/releases/tags/{version}')
    if verified['id'] != release['id'] or verified['draft'] or verified['prerelease']:
        raise RuntimeError('The GitHub release was not published as a stable release')
    print(f'Published {verified["html_url"]}')
    if version == '1.5.1':
        obsolete = request('GET', '/releases/tags/1.5.0', allow_missing=True)
        if obsolete is not None:
            request('DELETE', f'/releases/{obsolete["id"]}')
        if request('GET', '/releases/tags/1.5.0', allow_missing=True) is not None:
            raise RuntimeError('The obsolete 1.5.0 release page still exists')
        print('Confirmed no 1.5.0 release page remains; existing Git tags are preserved.')


if __name__ == '__main__':
    main()
