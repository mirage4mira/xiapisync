<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PHPExcel;
class InventoryTemplateExport implements FromArray, ShouldAutoSize, WithEvents
{
    protected $array;
    use RegistersEventListeners;
    public function __construct(array $array)
    {
        $this->array = $array;
    }

    public function array(): array
    {
        return $this->array;
    }

    public static function afterSheet(AfterSheet $event)
    {
        $sheet = $event->sheet->getDelegate();

        // $sheet->getStyle('1')->getFont()->setSize(16);
        $sheet->getStyle('D2:G'.$sheet->getHighestRow())
            ->applyFromArray(['font'=> ['color' => ['rgb' => '228B22']]]);
        // ...

        // $sheet->loadView('template');
$sheet->getProtection()->setPassword('password');
$sheet->getProtection()->setSheet(true);
$sheet->getStyle('D2:G'.$sheet->getHighestRow())->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);

    }
}