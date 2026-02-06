<?php
/**
 * Validator Tests
 *
 * Tests for Validator class
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ValidationException;

class ValidatorTest extends TestCase {
    /**
     * Test string validation with valid input
     *
     * @return void
     */
    public function testStringValidationWithValidInput(): void {
        $result = Validator::string('test string');

        $this->assertEquals('test string', $result);
        $this->assertIsString($result);
    }

    /**
     * Test string validation trims whitespace
     *
     * @return void
     */
    public function testStringValidationTrimsWhitespace(): void {
        $result = Validator::string('  test string  ');

        $this->assertEquals('test string', $result);
    }

    /**
     * Test string validation escapes HTML
     *
     * @return void
     */
    public function testStringValidationEscapesHTML(): void {
        $result = Validator::string('<script>alert("xss")</script>');

        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Test string validation enforces max length
     *
     * @return void
     */
    public function testStringValidationEnforcesMaxLength(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must not exceed');

        Validator::string('a', 5); // length 1, max 5 - should pass
        Validator::string('a', 0); // length 1, max 0 - should fail
    }

    /**
     * Test string validation requires value by default
     *
     * @return void
     */
    public function testStringValidationRequiresValueByDefault(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('required');

        Validator::string('');
    }

    /**
     * Test string validation allows empty when not required
     *
     * @return void
     */
    public function testStringValidationAllowsEmptyWhenNotRequired(): void {
        $result = Validator::string('', 255, false);

        $this->assertEquals('', $result);
    }

    /**
     * Test email validation with valid email
     *
     * @return void
     */
    public function testEmailValidationWithValidEmail(): void {
        $result = Validator::email('test@example.com');

        $this->assertEquals('test@example.com', $result);
    }

    /**
     * Test email validation rejects invalid email
     *
     * @return void
     */
    public function testEmailValidationRejectsInvalidEmail(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid email');

        Validator::email('invalid-email');
    }

    /**
     * Test email validation trims whitespace
     *
     * @return void
     */
    public function testEmailValidationTrimsWhitespace(): void {
        $result = Validator::email('  test@example.com  ');

        $this->assertEquals('test@example.com', $result);
    }

    /**
     * Test email validation requires value by default
     *
     * @return void
     */
    public function testEmailValidationRequiresValueByDefault(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('required');

        Validator::email('');
    }

    /**
     * Test email validation allows empty when not required
     *
     * @return void
     */
    public function testEmailValidationAllowsEmptyWhenNotRequired(): void {
        $result = Validator::email('', false);

        $this->assertEquals('', $result);
    }

    /**
     * Test integer validation with valid integer
     *
     * @return void
     */
    public function testIntegerValidationWithValidInteger(): void {
        $result = Validator::integer('42');

        $this->assertEquals(42, $result);
        $this->assertIsInt($result);
    }

    /**
     * Test integer validation rejects non-numeric
     *
     * @return void
     */
    public function testIntegerValidationRejectsNonNumeric(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be a number');

        Validator::integer('abc');
    }

    /**
     * Test integer validation enforces minimum
     *
     * @return void
     */
    public function testIntegerValidationEnforcesMinimum(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('at least');

        Validator::integer('5', 10);
    }

    /**
     * Test integer validation enforces maximum
     *
     * @return void
     */
    public function testIntegerValidationEnforcesMaximum(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must not exceed');

        Validator::integer('15', 0, 10);
    }

    /**
     * Test integer validation accepts zero
     *
     * @return void
     */
    public function testIntegerValidationAcceptsZero(): void {
        $result = Validator::integer('0');

        $this->assertEquals(0, $result);
    }

    /**
     * Test integer validation allows empty when not required
     *
     * @return void
     */
    public function testIntegerValidationAllowsEmptyWhenNotRequired(): void {
        $result = Validator::integer('', 0, null, false);

        $this->assertEquals(0, $result);
    }

    /**
     * Test price validation with valid price
     *
     * @return void
     */
    public function testPriceValidationWithValidPrice(): void {
        $result = Validator::price('19.99');

        $this->assertEquals(19.99, $result);
        $this->assertIsFloat($result);
    }

    /**
     * Test price validation rejects non-numeric
     *
     * @return void
     */
    public function testPriceValidationRejectsNonNumeric(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be a number');

        Validator::price('abc');
    }

    /**
     * Test price validation rejects negative prices
     *
     * @return void
     */
    public function testPriceValidationRejectsNegativePrices(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cannot be negative');

        Validator::price('-10.00');
    }

    /**
     * Test price validation rounds to 2 decimals
     *
     * @return void
     */
    public function testPriceValidationRoundsToTwoDecimals(): void {
        $result = Validator::price('19.999');

        $this->assertEquals(20.00, $result);
        $this->assertEquals(2, strlen(substr(strrchr((string) $result, '.'), 1)));
    }

    /**
     * Test price validation allows zero
     *
     * @return void
     */
    public function testPriceValidationAllowsZero(): void {
        $result = Validator::price('0.00');

        $this->assertEquals(0.00, $result);
    }

    /**
     * Test slug validation with valid slug
     *
     * @return void
     */
    public function testSlugValidationWithValidSlug(): void {
        $result = Validator::slug('test-product-slug');

        $this->assertEquals('test-product-slug', $result);
    }

    /**
     * Test slug validation converts to lowercase
     *
     * @return void
     */
    public function testSlugValidationConvertsToLowercase(): void {
        $result = Validator::slug('TEST-PRODUCT-SLUG');

        $this->assertEquals('test-product-slug', $result);
    }

    /**
     * Test slug validation replaces spaces with hyphens
     *
     * @return void
     */
    public function testSlugValidationReplacesSpacesWithHyphens(): void {
        $result = Validator::slug('test product slug');

        $this->assertEquals('test-product-slug', $result);
    }

    /**
     * Test slug validation removes special characters
     *
     * @return void
     */
    public function testSlugValidationRemovesSpecialCharacters(): void {
        $result = Validator::slug('test@product#$slug');

        $this->assertEquals('test-product-slug', $result);
    }

    /**
     * Test slug validation trims hyphens
     *
     * @return void
     */
    public function testSlugValidationTrimsHyphens(): void {
        $result = Validator::slug('-test-product-slug-');

        $this->assertEquals('test-product-slug', $result);
    }

    /**
     * Test boolean validation with true value
     *
     * @return void
     */
    public function testBooleanValidationWithTrueValue(): void {
        $result = Validator::boolean(true);

        $this->assertTrue($result);
    }

    /**
     * Test boolean validation with false value
     *
     * @return void
     */
    public function testBooleanValidationWithFalseValue(): void {
        $result = Validator::boolean(false);

        $this->assertFalse($result);
    }

    /**
     * Test boolean validation with numeric 1
     *
     * @return void
     */
    public function testBooleanValidationWithNumericOne(): void {
        $result = Validator::boolean(1);

        $this->assertTrue($result);
    }

    /**
     * Test boolean validation with numeric 0
     *
     * @return void
     */
    public function testBooleanValidationWithNumericZero(): void {
        $result = Validator::boolean(0);

        $this->assertFalse($result);
    }

    /**
     * Test boolean validation with string "true"
     *
     * @return void
     */
    public function testBooleanValidationWithStringTrue(): void {
        $result = Validator::boolean('true');

        $this->assertTrue($result);
    }

    /**
     * Test boolean validation with string "false"
     *
     * @return void
     */
    public function testBooleanValidationWithStringFalse(): void {
        $result = Validator::boolean('false');

        $this->assertFalse($result);
    }

    /**
     * Test boolean validation with string "1"
     *
     * @return void
     */
    public function testBooleanValidationWithStringOne(): void {
        $result = Validator::boolean('1');

        $this->assertTrue($result);
    }

    /**
     * Test boolean validation with string "yes"
     *
     * @return void
     */
    public function testBooleanValidationWithStringYes(): void {
        $result = Validator::boolean('yes');

        $this->assertTrue($result);
    }

    /**
     * Test boolean validation with string "on"
     *
     * @return void
     */
    public function testBooleanValidationWithStringOn(): void {
        $result = Validator::boolean('on');

        $this->assertTrue($result);
    }

    /**
     * Test boolean validation uses default for invalid values
     *
     * @return void
     */
    public function testBooleanValidationUsesDefaultForInvalidValues(): void {
        $result = Validator::boolean('invalid', true);

        $this->assertTrue($result);
    }

    /**
     * Test array validation with valid array
     *
     * @return void
     */
    public function testArrayValidationWithValidArray(): void {
        $input = ['foo', 'bar'];
        $result = Validator::array($input);

        $this->assertEquals($input, $result);
    }

    /**
     * Test array validation rejects non-array
     *
     * @return void
     */
    public function testArrayValidationRejectsNonArray(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must be an array');

        Validator::array('not an array');
    }

    /**
     * Test array validation allows empty when not required
     *
     * @return void
     */
    public function testArrayValidationAllowsEmptyWhenNotRequired(): void {
        $result = Validator::array([], false);

        $this->assertEquals([], $result);
    }

    /**
     * Test phone validation with valid phone
     *
     * @return void
     */
    public function testPhoneValidationWithValidPhone(): void {
        $result = Validator::phone('+27 82 123 4567');

        $this->assertEquals('27821234567', $result);
    }

    /**
     * Test phone validation removes non-numeric characters
     *
     * @return void
     */
    public function testPhoneValidationRemovesNonNumericCharacters(): void {
        $result = Validator::phone('+27 (082) 123-4567');

        $this->assertEquals('270821234567', $result);
    }

    /**
     * Test phone validation rejects too short numbers
     *
     * @return void
     */
    public function testPhoneValidationRejectsTooShortNumbers(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid phone number');

        Validator::phone('123456789');
    }

    /**
     * Test phone validation rejects too long numbers
     *
     * @return void
     */
    public function testPhoneValidationRejectsTooLongNumbers(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid phone number');

        Validator::phone('1234567890123456');
    }

    /**
     * Test phone validation allows null when not required
     *
     * @return void
     */
    public function testPhoneValidationAllowsNullWhenNotRequired(): void {
        $result = Validator::phone('', false);

        $this->assertNull($result);
    }

    /**
     * Test URL validation with valid URL
     *
     * @return void
     */
    public function testUrlValidationWithValidUrl(): void {
        $result = Validator::url('https://example.com');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * Test URL validation rejects invalid URL
     *
     * @return void
     */
    public function testUrlValidationRejectsInvalidUrl(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid URL');

        Validator::url('not-a-url');
    }

    /**
     * Test date validation with valid date
     *
     * @return void
     */
    public function testDateValidationWithValidDate(): void {
        $result = Validator::date('2024-01-15');

        $this->assertEquals('2024-01-15', $result);
    }

    /**
     * Test date validation rejects invalid format
     *
     * @return void
     */
    public function testDateValidationRejectsInvalidFormat(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid date format');

        Validator::date('01/15/2024');
    }

    /**
     * Test date validation allows null when not required
     *
     * @return void
     */
    public function testDateValidationAllowsNullWhenNotRequired(): void {
        $result = Validator::date('', 'Y-m-d', false);

        $this->assertNull($result);
    }

    /**
     * Test enum validation with valid value
     *
     * @return void
     */
    public function testEnumValidationWithValidValue(): void {
        $result = Validator::enum('active', ['active', 'inactive', 'pending']);

        $this->assertEquals('active', $result);
    }

    /**
     * Test enum validation rejects invalid value
     *
     * @return void
     */
    public function testEnumValidationRejectsInvalidValue(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid value. Allowed:');

        Validator::enum('invalid', ['active', 'inactive']);
    }

    /**
     * Test text validation with long text
     *
     * @return void
     */
    public function testTextValidationWithLongText(): void {
        $text = str_repeat('a', 1000);
        $result = Validator::text($text, 5000);

        $this->assertEquals($text, $result);
    }

    /**
     * Test text validation enforces max length
     *
     * @return void
     */
    public function testTextValidationEnforcesMaxLength(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('must not exceed');

        Validator::text(str_repeat('a', 6000), 5000);
    }

    /**
     * Test validate method with multiple fields
     *
     * @return void
     */
    public function testValidateMethodWithMultipleFields(): void {
        $rules = [
            'name' => ['string', 'required' => true, 'max' => 100],
            'email' => ['email', 'required' => true],
            'age' => ['integer', 'required' => true, 'min' => 18],
        ];

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => '25',
        ];

        $result = Validator::validate($rules, $data);

        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
        $this->assertEquals(25, $result['age']);
    }

    /**
     * Test validate method throws exception on validation failure
     *
     * @return void
     */
    public function testValidateMethodThrowsExceptionOnFailure(): void {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation failed');

        $rules = [
            'email' => ['email', 'required' => true],
        ];

        $data = [
            'email' => 'invalid-email',
        ];

        Validator::validate($rules, $data);
    }

    /**
     * Test validate method includes field errors
     *
     * @return void
     */
    public function testValidateMethodIncludesFieldErrors(): void {
        $this->expectException(ValidationException::class);

        $rules = [
            'email' => ['email', 'required' => true],
            'name' => ['string', 'required' => true],
        ];

        $data = [
            'email' => 'invalid',
            'name' => '',
        ];

        try {
            Validator::validate($rules, $data);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('name', $errors);

            throw $e;
        }
    }

    /**
     * Test HTML sanitization preserves allowed tags
     *
     * @return void
     */
    public function testHtmlSanitizationPreservesAllowedTags(): void {
        $result = Validator::html('<p>Hello <b>world</b></p>', ['<p>', '<b>']);

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<b>', $result);
    }

    /**
     * Test HTML sanitization removes disallowed tags
     *
     * @return void
     */
    public function testHtmlSanitizationRemovesDisallowedTags(): void {
        $result = Validator::html('<p>Hello <script>alert("xss")</script></p>', ['<p>']);

        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Test HTML sanitization escapes all when no allowed tags
     *
     * @return void
     */
    public function testHtmlSanitizationEscapesAllWhenNoAllowedTags(): void {
        $result = Validator::html('<p>Hello <b>world</b></p>');

        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringNotContainsString('<p>', $result);
    }
}
