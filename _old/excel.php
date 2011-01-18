<?php
	class excel_style {
		public $name, $info;
	
		function __construct($name, $info = null) {
			$this->name = $name;
			$this->info = $info;
		}
		
		static public function set_default(&$var, $default) {
			if (!isset($var)) $var = $default;
		}
	
		public function out($f = STDOUT) { $i = &$this->info;
			fwrite($f, "<Style ss:ID=\"" . ($this->name) . "\">\n");
			
			self::set_default($i['halign'], 'Left');
			self::set_default($i['valign'], 'Center');
			self::set_default($i['bgcolor'], '');
			self::set_default($i['color'], '#000000');
			self::set_default($i['bold'], false);
			self::set_default($i['family'], 'Swiss');
			self::set_default($i['wrap'], true);
			
			fwrite($f, '<Alignment ss:Horizontal="' . htmlspecialchars($i['halign']) . '" ss:Vertical="' . htmlspecialchars($i['valign']) . '" ss:WrapText="' . (int)$i['wrap'] . '"/>');
			if (strlen($i['bgcolor'])) {
				fwrite($f, '<Interior ss:Color="' . htmlspecialchars($i['bgcolor']) . '" ss:Pattern="Solid"/>');
			}
			fwrite($f, '<Font x:Family="' . htmlspecialchars($i['family']) . '" ss:Color="' . htmlspecialchars($i['color']) . '" ss:Bold="' . (int)$i['bold'] . '"/>');
			
			fwrite($f, "</Style>\n");
		}
	}

	class excel_worksheet {
		public  $name;
		private $columns = array();
		private $rows = array();
		private $max_columns = 0;
		public $fixed_rows = 0;
		
		public function __construct($name = 'Untitled') {
			$this->name = $name;
		}
		
		public function addColumn($width = 40, $autofit = false, $style = 'default') {
			$this->columns[] = array($width, $autofit, $style);
		}
		
		public function addRow($row, $style = 'default') {
			$this->rows[] = array($row, $style);
			$this->max_columns = max($this->max_columns, sizeof($row));
		}
		
		public function out($f = STDOUT) {
			fwrite($f, '<Worksheet ss:Name="' . htmlspecialchars($this->name) . '">' . "\n");
				fwrite($f, "\t<Table ss:ExpandedColumnCount=\"" . (int)$this->max_columns . '" ss:ExpandedRowCount="' . sizeof($this->rows) . '" x:FullColumns="1" x:FullRows="1" ss:DefaultColumnWidth="60">' . "\n");
				
				$styles[$n] = array();
				
				for ($n = 0; $n < $this->max_columns; $n++) {
					list($_width, $_autofit, $_style) = isset($this->columns[$n]) ? $this->columns[$n] : array(0, false, $style);
					fwrite($f, "\t\t<Column ss:AutoFitWidth=\"" . (int)$_autofit . '" ss:Width="' . (int)$_width . '" />' . "\n");
					$styles[$n] = $_style;
				}
				foreach ($this->rows as $_row) { list($row, $style) = $_row;
					fwrite($f, "\t\t<Row>\n");
						for ($n = 0; $n < $this->max_columns; $n++) {
							if (is_array($style)) {
								$cstyle = $style[$n];
							} else {
								$cstyle = $style;
							}
							//echo "{$cstyle},";
							//var_dump($row[$n]);
							if (isset($row[$n])) {
								
								switch (gettype($row[$n])) {
									default: $type = 'String';
										if (is_numeric($row[$n])) {
											$type = 'Number';
										}
									break;
									case 'integer': case 'double':
										$type = 'Number';
									break;
								}
								fwrite($f, "\t\t\t<Cell ss:StyleID=\"" . htmlspecialchars($cstyle) . '">');
								fwrite($f, '<Data ss:Type="' . htmlspecialchars($type) . '">' . htmlspecialchars($row[$n]) . '</Data>');
								fwrite($f, "</Cell>\n");
							}
						}
						//echo "\n";
					fwrite($f, "\t\t</Row>\n");
				}
				fwrite($f, "\t</Table>\n");
				
				$freeze_panes = '';
				if ($this->fixed_rows > 0) {
					$fixed_rows = (int)$this->fixed_rows;
					$freeze_panes = (
						"<FreezePanes/>" .
						"<SplitHorizontal>{$fixed_rows}</SplitHorizontal>" .
						"<TopRowBottomPane>{$fixed_rows}</TopRowBottomPane>" .
						"<ActivePane>2</ActivePane>"
					);
				}
				
				fwrite($f, <<<DATA
	<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
		<PageSetup>
			<Header x:Margin="0"/>
			<Footer x:Margin="0"/>
			<PageMargins x:Bottom="0.984251969" x:Left="0.78740157499999996" x:Right="0.78740157499999996" x:Top="0.984251969"/>
		</PageSetup>
		<Selected/>
		<ProtectObjects>False</ProtectObjects>
		<ProtectScenarios>False</ProtectScenarios>
		{$freeze_panes}
	</WorksheetOptions>
DATA
);
				if ($this->fixed_rows == 1) {
					/*
					for ($n = 1; $n <= $this->max_columns; $n++) {
						//fwrite($f, '<AutoFilter x:Range="R1C' . $n . ':R' . sizeof($this->rows) . 'C' . $n . '" xmlns="urn:schemas-microsoft-com:office:excel"></AutoFilter>' . "\n");
					}
					*/
					fwrite($f, '<AutoFilter x:Range="R1C1:R1C' . $this->max_columns . '" xmlns="urn:schemas-microsoft-com:office:excel"></AutoFilter>');
				}
			fwrite($f, '</Worksheet>');
		}
	}

	class excel {
		public $styles = array();
		public $worksheets = array();
		
		public function __construct() {
			$this->addStyle('default', array());
		}
		
		public function addWorksheet($name) {
			$this->worksheets[] = $page = new excel_worksheet($name);
			return $page;
		}

		public function addStyle($name, $info = null) {
			$this->styles[$name] = new excel_style($name, $info);
		}

		public function out($f = null) {
			if ($f === null) $f = fopen('php://output', 'rb');

			fwrite($f, 
				'<?'.'xml version="1.0"?'.'><'.'?mso-application progid="Excel.Sheet"?'.'>' .
				'<Workbook ' .
					'xmlns="urn:schemas-microsoft-com:office:spreadsheet" ' .
					'xmlns:o="urn:schemas-microsoft-com:office:office" ' .
					'xmlns:x="urn:schemas-microsoft-com:office:excel" ' .
					'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" ' .
					'xmlns:html="http://www.w3.org/TR/REC-html40"' .
				">\n"
			);
			{
				fwrite($f, "<Styles>\n");
					foreach ($this->styles as $style) $style->out($f);
				fwrite($f, "</Styles>\n");

				foreach ($this->worksheets as $worksheet) $worksheet->out($f);
			}
			fwrite($f, "\n</Workbook>\n");
		}
	}
?>