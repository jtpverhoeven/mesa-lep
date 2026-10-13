<?php

namespace Tests\Unit\Actions\PrintProfiles;

use App\Actions\PrintProfiles\ParsePrintProfile;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParsePrintProfileTest extends TestCase
{
    public function test_preserves_file_order_and_parses_legacy_commands_and_line_endings(): void
    {
        $script = "# profile\r\n10 SAMPLE HASANY [12, \"34\"] GOTO 40 ELSE 70\r\n40 ANALYSIS ONLY [12] LABEL 2 PRINTER 3 COPIES 4 SINGLE\r70 SAMPLE PRINT 2 PRINTER 3 COPIES 1\n20 EXIT";

        $instructions = (new ParsePrintProfile)->handle($script);

        $this->assertSame([10, 40, 70, 20], array_keys($instructions));
        $this->assertSame(['command' => 'SAMPLE', 'mode' => 'HASANY', 'ids' => [12, 34], 'goto' => 40, 'else' => 70], $instructions[10]);
        $this->assertSame(['command' => 'ANALYSIS', 'mode' => 'ONLY', 'ids' => [12], 'single' => true, 'label' => 2, 'printer' => 3, 'copies' => 4], $instructions[40]);
    }

    #[DataProvider('commands')]
    public function test_accepts_all_legacy_instruction_modes(string $command, string $expectedMode): void
    {
        $instructions = (new ParsePrintProfile)->handle("10 $command\n20 EXIT");

        $this->assertSame($expectedMode, $instructions[10]['mode'] ?? $instructions[10]['command']);
    }

    #[DataProvider('invalidScripts')]
    public function test_rejects_invalid_scripts_before_execution(string $script): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ParsePrintProfile)->handle($script);
    }

    public static function commands(): array
    {
        return [
            ['GOTO 20', 'GOTO'],
            ['LOG printing sample', 'LOG'],
            ['COMMENT note', 'COMMENT'],
            ['SAMPLE HASONLYONE GOTO 20', 'HASONLYONE'],
            ['SAMPLE HASALL [1] GOTO 20 ELSE 20', 'HASALL'],
            ['SAMPLE HASANYWITHCONFIRMATION [1] GOTO 20', 'HASANYWITHCONFIRMATION'],
            ['ANALYSIS ANY LABEL 1 PRINTER 2 COPIES 1', 'ANY'],
            ['ANALYSIS NOT [1] LABEL 1 PRINTER 2 COPIES 1', 'NOT'],
            ['ANALYSIS GROUP [1] LABEL 1 PRINTER 2 COPIES 1', 'GROUP'],
            ['ANALYSIS NOTGROUP [1] LABEL 1 PRINTER 2 COPIES 1', 'NOTGROUP'],
            ['ANALYSIS ONLYWITHCONFIRMATION {"0":"1"} LABEL 1 PRINTER 2 COPIES 1 SINGLE', 'ONLYWITHCONFIRMATION'],
        ];
    }

    public static function invalidScripts(): array
    {
        return [
            'empty' => ['# comment'],
            'missing line' => ['EXIT'],
            'duplicate line' => ["10 EXIT\n10 EXIT"],
            'missing target' => ['10 GOTO 40'],
            'missing else target' => ["10 SAMPLE HASANY [1] GOTO 20 ELSE 30\n20 EXIT"],
            'unknown command' => ['10 EXEC shell'],
            'broken JSON' => ["10 SAMPLE HASANY [broken] GOTO 20\n20 EXIT"],
            'nested JSON' => ['10 ANALYSIS ONLY [[1]] LABEL 1 PRINTER 2 COPIES 1'],
            'negative ID' => ['10 ANALYSIS ONLY [-1] LABEL 1 PRINTER 2 COPIES 1'],
            'zero copies' => ['10 SAMPLE PRINT 1 PRINTER 2 COPIES 0'],
            'excessive copies' => ['10 SAMPLE PRINT 1 PRINTER 2 COPIES 10000'],
            'missing parameters' => ['10 ANALYSIS ANY'],
        ];
    }
}
