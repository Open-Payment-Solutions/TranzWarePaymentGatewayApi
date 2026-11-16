<?php

namespace tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function OpenPaymentSolutions\TranzWarePaymentGateway\toPascalCase;
use function OpenPaymentSolutions\TranzWarePaymentGateway\getUserAgent;

final class HelpersTest extends TestCase
{
    /**
     * Build the default User-Agent from the package version and preserve optional overrides.
     *
     * @return void
     */
    public function testUserAgentUsesVersionAndAllowsOverrides(): void
    {
        $expected = 'OpenPaymentSolutions/TranzWarePaymentGateway/v'
            . trim(file_get_contents(__DIR__ . '/../VERSION'));
        $this->assertSame($expected, getUserAgent());
        $this->assertSame('MyApplication/2.0', getUserAgent('MyApplication/2.0'));
        $this->assertSame($expected, getUserAgent(null));
    }

    /**
     * Reject empty or multiline User-Agent values before sending request headers.
     *
     * @param string $value
     *
     * @see    self::invalidUserAgents()
     * @return void
     */
    #[DataProvider('invalidUserAgents')]
    public function testInvalidUserAgentIsRejected(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        getUserAgent($value);
    }

    /**
     * Provide empty values and attempts to inject additional headers.
     *
     * @return array
     */
    public static function invalidUserAgents(): array
    {
        return [[''], [' '], ["\t"], ["Client\r\nX-Injected: true"], ["Client\nInjected"],
            ["Client\rInjected"], ["Client\0Injected"]];
    }

    /**
     * Convert naming conventions without changing existing PascalCase behavior.
     *
     * @param string $input
     * @param string $expected
     *
     * @see    self::pascalCaseValues()
     * @return void
     */
    #[DataProvider('pascalCaseValues')]
    public function testToPascalCase(string $input, string $expected): void
    {
        $this->assertSame($expected, toPascalCase($input));
    }

    /**
     * Provide naming conventions, separators, and Unicode strings.
     *
     * @return array
     */
    public static function pascalCaseValues(): array
    {
        return [
            'empty' => ['', ''],
            'lowercase' => ['purchase', 'Purchase'],
            'uppercase' => ['PURCHASE', 'Purchase'],
            'pascal case' => ['PreAuth', 'PreAuth'],
            'camel case' => ['preAuth', 'PreAuth'],
            'snake case' => ['pre_auth', 'PreAuth'],
            'macro case' => ['PRE_AUTH', 'PreAuth'],
            'kebab case' => ['pre-auth', 'PreAuth'],
            'space separated' => ['pre auth', 'PreAuth'],
            'dot separated' => ['pre.auth', 'PreAuth'],
            'repeated underscores' => ['__PRE__AUTH__', 'PreAuth'],
            'repeated dashes' => ['--PRE--AUTH--', 'PreAuth'],
            'repeated spaces' => ['  PRE  AUTH  ', 'PreAuth'],
            'repeated dots' => ['..PRE..AUTH..', 'PreAuth'],
            'only underscores' => ['___', ''],
            'only dashes' => ['---', ''],
            'only spaces' => ['   ', ''],
            'only dots' => ['...', ''],
            'uppercase single word' => ['PREAUTH', 'Preauth'],
            'trailing acronym' => ['PreAUTH', 'PreAUTH'],
            'leading acronym' => ['XMLHttpRequest', 'XMLHttpRequest'],
            'separated acronyms' => ['XML_HTTP_request', 'XmlHttpRequest'],
            'underscore before dash' => ['pre-auth_test', 'Pre-AuthTest'],
            'dash after underscore' => ['pre_auth-test', 'PreAuth-Test'],
            'space before dot' => ['pre auth.test', 'PreAuth.test'],
            'tab retained' => ["pre\tauth", "Pre\tAuth"],
            'digits' => ['pre2Auth', 'Pre2Auth'],
            'uppercase with digits' => ['PRE2AUTH', 'PRE2AUTH'],
            'accented letters' => ['école_ÉTÉ', 'ÉcoleÉté'],
            'cyrillic' => ['привет_МИР', 'ПриветМир'],
            'german sharp s' => ['straße_test', 'StraßeTest'],
            'unicode titlecase' => ['ǅuro_test', 'ǅuroTest'],
            'turkish dotted i' => ['test_İSTANBUL', 'Testİstanbul'],
            'punctuation retained' => ['?!', '?!'],
        ];
    }

    /**
     * Reject malformed UTF-8 rather than replacing invalid bytes.
     *
     * @param string $input
     *
     * @see    self::invalidEncodingValues()
     * @return void
     */
    #[DataProvider('invalidEncodingValues')]
    public function testInvalidEncodingReturnsEmptyString(string $input): void
    {
        $this->assertSame('', toPascalCase($input));
    }

    /**
     * Provide malformed UTF-8 with and without word separators.
     *
     * @return array
     */
    public static function invalidEncodingValues(): array
    {
        return [["Pre\xffAuth"], ["Pre\xff_Auth"], ["Pre\xff-Auth"], ["Pre\xff Auth"],
            ["Pre\xff.Auth"], ["\xc3"]];
    }

    /**
     * Keep UTF-8 conversion independent of the application's default encoding.
     *
     * @return void
     */
    public function testConversionUsesExplicitUtf8Encoding(): void
    {
        $encoding = mb_internal_encoding();
        try {
            mb_internal_encoding('ISO-8859-1');
            $this->assertSame('ÉcoleÉté', toPascalCase('école_ÉTÉ'));
        } finally {
            mb_internal_encoding($encoding);
        }
    }
}
