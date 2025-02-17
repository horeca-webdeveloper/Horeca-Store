<?php

namespace App\Repository;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class ExcelRepository
{
	/**
	 * To create new spreadsheet
	 * @return Spreadsheet
	 */
	public function newSpreadsheet()
	{
		$spreadsheet = new Spreadsheet;
		return $spreadsheet;
	}

	/**
	 * To Set header of excel export file
	 * @return Spreadsheet
	 */
	public function setHeader($activeSheet, $headerArray)
	{
		$styleArray =
		[
			'alignment' => [
				'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
				'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
			],
		];

		$row = 1;
		$col = 'A';
		foreach ($headerArray as $header) {
			$activeSheet->setCellValue($col . $row, $header);
			$activeSheet->getStyle($col . $row)->applyFromArray($styleArray);
			$col++;
		}
		$row++;
	}

	/**
	 * To Set dropdown in excel export file
	 * @return Spreadsheet
	 */
	public function setDropdown($sheet, $cell, $dropdownVals)
	{
		if (!is_array($dropdownVals) || empty($dropdownVals)) {
			dd('array issue');
			return;
		}

		$validation = $sheet->getCell($cell)->getDataValidation();
		$validation->setType(DataValidation::TYPE_LIST);
		$validation->setErrorStyle(DataValidation::STYLE_STOP);
		$validation->setAllowBlank(true);
		$validation->setShowInputMessage(true);
		$validation->setShowDropDown(true);
		$validation->setShowErrorMessage(true);
		$validation->setErrorTitle('Invalid Selection');
		$validation->setError('Please select a value from the dropdown list.');

		$validation->setFormula1('"' . implode(',', $dropdownVals) . '"');

		$sheet->getCell($cell)->setDataValidation($validation);
	}



	/**
	 * To Set the border in excel file
	 * @return Spreadsheet
	 */
	public function setBorder($spreadsheet, $range)
	{
		$spreadsheet->getActiveSheet()->getStyle($range)->applyFromArray([
			'borders' => [
				'allBorders' => [
					'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
					'color' => ['argb' => '000000'],
				],
			],
		]);
	}

	/**
	 * Excel function to load a excel file and return reader object
	 * @param $fileName
	 * @return Spreadsheet
	 */
	public function loadFile($fileName) {
		// $reader =IOFactory::createReaderForFile($fileName);
		// return $reader->load($fileName);
		return IOFactory::load($fileName);
	}

	/**
	 * Function to download excel file based on given filename and excelObject
	 * @param string $fileName
	 * @param $excelObject
	 */
	public function downloadFile($fileName, $excelObject) {

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename=' . $fileName);
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
		header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header('Pragma: public'); // HTTP/1.0
		$writer = IOFactory::createWriter($excelObject, 'Xlsx');

		$writer->save('php://output');
		exit;
	}

	/**
	 * To save the excel file at the given folder
	 */
	public function saveFile($fileNameWithPath, $excelObject) {
		$writer = IOFactory::createWriter($excelObject, "Xlsx");
		$writer->save($fileNameWithPath);
		// return;
	}

	/**
	 * To save the excel file at the given folder
	 */
	public function saveCsvFile($fileNameWithPath, $csvObject) {
		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($csvObject);
		$writer->save($fileNameWithPath);
		// return;
	}

	/**
	 * Get all worksheet details
	 */
	public function getAllWorksheetInfo($fileName)
	{
		$reader = new Reader();
		$worksheetInfo = $reader->listWorksheetInfo($fileName);
		return $worksheetInfo;
	}

	/**
	 * Excel function to load a excel file and return reader object
	 * @param $fileName
	 * @return Spreadsheet
	 */
	public function loadExcelFileData($fileName, $worksheetName, $startRow, $endRow, $lastColumnLetter)
	{
		$reader = new Reader();
		$worksheetList = $reader->listWorksheetNames($fileName);
		$reader->setReadDataOnly(true);
		$reader->setReadEmptyCells(false);
		$reader->setLoadSheetsOnly([$worksheetName]);
		$chunkFilter = new ChunkReadFilter();

		// Tell the Reader that we want to use the Read Filter that we've Instantiated
		$reader->setReadFilter($chunkFilter);

		// Tell the Read Filter, the limits on which rows we want to read this iteration
		$chunkFilter->setRows($startRow, $endRow);

		// Load only the rows that match our filter from $inputFileName to a PhpSpreadsheet Object
		$spreadsheet = $reader->load($fileName);

		$sheet = $spreadsheet->getSheetByName($worksheetName);

		// $maxDataRow = $sheet->getHighestDataRow();
		// return $sheet->rangeToArray("A{$startRow}:{$lastColumnLetter}{$maxDataRow}");
		return $sheet->rangeToArray("A{$startRow}:{$lastColumnLetter}{$endRow}");
	}
}

/**
 * Define a Read Filter class implementing \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
 */
// class ChunkReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
// {
// 	private $startRow = 0;
// 	private $endRow   = 0;

// 	/**  Set the list of rows that we want to read  */
// 	public function setRows($startRow, $endRow) {
// 		$this->startRow = $startRow;
// 		$this->endRow = $endRow;
// 	}

// 	public function readCell(string $column, int $row, string $worksheetName = ''):bool {
// 		# Only read the heading row, and the configured rows
// 		if ($row >= $this->startRow && $row <= $this->endRow) {
// 			return true;
// 		}
// 		return false;
// 	}
// }