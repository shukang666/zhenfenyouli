<?php

namespace api\wxapp\controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
class ExcelExporter
{
    /**
     * 主导出方法
     * @param array $data 导出数据
     * @param array $headerRow 表头配置
     * @param array $conf 导出配置
     * @param array $subtitle 副标题配置
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function excelExport(array $data, array $headerRow, array $conf = [], array $subtitle = [])
    {
        // 记录开始时间（毫秒级精度）
        $startTime = microtime(true);
        // 输入验证
        if (empty($data) || empty($headerRow)) {
            throw new \InvalidArgumentException('导出数据和表头不能为空');
        }

        // 初始化Excel对象
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 设置基础信息
        $fileName = $this->generateFileName($conf);
        $format = $this->validateFormat($conf['format'] ?? 'Xlsx');
        $sheet->setTitle(substr($fileName, 0, 31)); // 限制sheet名称长度

        try {
            // 构建Excel内容
            $this->buildExcelContent($sheet, $data, $headerRow, $fileName, $subtitle);

            // 输出文件
            $this->outputExcel($spreadsheet, $fileName, $format);
        } finally {
            // 确保释放内存
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    /**
     * 生成文件名
     */
    protected function generateFileName(array $conf): string
    {
        return $conf['fileName'] ?? date('YmdHis') . bin2hex(random_bytes(4));
    }

    /**
     * 验证文件格式
     */
    protected function validateFormat(string $format): string
    {
        $format = strtolower($format);
        return in_array($format, ['xlsx', 'xls', 'csv']) ? $format : 'xlsx';
    }

    /**
     * 构建Excel内容
     */
    protected function buildExcelContent($sheet, $data, $headerRow, $fileName, $subtitle)
    {
        $currentRow = 1;

        // 主标题
        if (!empty($fileName)) {
            $this->setMainTitle($sheet, $fileName, count($headerRow));
            $currentRow++;
        }

        // 副标题
        if (!empty($subtitle)) {
            $this->setSubtitle($sheet, $subtitle, $currentRow);
            $currentRow++;
        }

        // 表头
        $this->setHeaders($sheet, $headerRow, $currentRow);
        $currentRow++;

        // 数据行
        $this->setDataRows($sheet, $data, $headerRow, $currentRow);

        // 全局样式
        $this->applyGlobalStyles($sheet, $headerRow, $data, $currentRow);
    }

    /**
     * 设置主标题
     */
    protected function setMainTitle($sheet, $title, $colCount)
    {
        $lastCol = $this->intToChr($colCount - 1);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $title);

        $style = [
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle('A1')->applyFromArray($style);
    }

    /**
     * 设置副标题
     */
    protected function setSubtitle($sheet, $subtitle, $startRow)
    {
        $colIndex = 0;
        foreach ($subtitle as $item) {
            $startCol = $this->intToChr($colIndex);
            $endCol = $this->intToChr($colIndex + ($item['acrossCells'] ?? 1) - 1);

            $sheet->setCellValue($startCol.$startRow, $item['rowName'] ?? '');
            $sheet->mergeCells("{$startCol}{$startRow}:{$endCol}{$startRow}");

            $colIndex += $item['acrossCells'] ?? 1;
        }
    }

    /**
     * 设置表头
     */
    protected function setHeaders($sheet, $headerRow, $rowNum)
    {
        foreach ($headerRow as $colIndex => $header) {
            $colLetter = $this->intToChr($colIndex);
            $cell = $colLetter.$rowNum;

            $headerName = is_array($header) ? ($header['rowName'] ?? '') : $header;
            $sheet->setCellValue($cell, $headerName);

            // 设置列宽
            if (is_array($header) && isset($header['width'])) {
                $sheet->getColumnDimension($colLetter)->setWidth($header['width']);
            }

            // 表头样式
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }
    }

    /**
     * 设置数据行
     */
    protected function setDataRows($sheet, $data, $headerRow, $startRow)
    {
        $isAssocHeader = isset($headerRow[0]['rowVal']);

        foreach ($data as $rowIndex => $rowData) {
            foreach ($headerRow as $colIndex => $header) {
                $cell = $this->intToChr($colIndex) . ($startRow + $rowIndex);

                $value = $isAssocHeader
                    ? ($rowData[$header['rowVal']] ?? '')
                    : ($rowData[$colIndex] ?? '');

                $sheet->setCellValue($cell, $value);

                // 特殊格式处理
                if ($isAssocHeader && isset($header['format'])) {
                    $this->formatCellValue($sheet, $cell, $value, $header['format']);
                }
            }
        }
    }

    /**
     * 单元格格式处理
     */
    protected function formatCellValue($sheet, $cell, $value, $format)
    {
        switch ($format) {
            case 'date':
                $sheet->getStyle($cell)->getNumberFormat()
                    ->setFormatCode('yyyy-mm-dd');
                break;
            case 'datetime':
                $sheet->getStyle($cell)->getNumberFormat()
                    ->setFormatCode('yyyy-mm-dd hh:mm:ss');
                break;
            case 'currency':
                $sheet->getStyle($cell)->getNumberFormat()
                    ->setFormatCode('#,##0.00');
                break;
        }
    }

    /**
     * 应用全局样式
     */
    protected function applyGlobalStyles($sheet, $headerRow, $data, $startRow)
    {
        $colCount = count($headerRow);
        $rowCount = count($data);
        $lastCol = $this->intToChr($colCount - 1);

        // 全局居中
        $sheet->getStyle("A1:{$lastCol}" . ($startRow + $rowCount - 1))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // 自动调整列宽
        foreach (range(0, $colCount - 1) as $col) {
            $sheet->getColumnDimension($this->intToChr($col))->setAutoSize(true);
        }

        // 冻结表头
        $sheet->freezePane('A' . $startRow);

        // 添加边框
        $sheet->getStyle("A1:{$lastCol}" . ($startRow + $rowCount - 1))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    }

    /**
     * 输出Excel文件
     */
    protected function outputExcel($spreadsheet, $fileName, $format)
    {
        // 设置HTTP头
        $contentTypes = [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'csv' => 'text/csv'
        ];

        header('Content-Type: ' . ($contentTypes[$format] ?? $contentTypes['xlsx']));
        header('Content-Disposition: attachment;filename="' . $fileName . '.' . $format . '"');
        header('Cache-Control: max-age=0');

        // 写入输出
        $writer = IOFactory::createWriter($spreadsheet, ucfirst($format));
        $writer->save('php://output');
    }

    /**
     * 数字转Excel列字母
     */
    protected function intToChr($index)
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
    }
}