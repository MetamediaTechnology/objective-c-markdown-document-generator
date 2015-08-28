<?php

define('INPUT_DIR', '.');
define('OUTPUT_DIR', 'output/');

$filenames = scandir(INPUT_DIR);
if (!file_exists(OUTPUT_DIR)) {
	mkdir(OUTPUT_DIR);
}

foreach ($filenames as $filename) {
	if (endsWith($filename, '.h')) {
		echo generateMarkdown($filename);
	}
}

function generateMarkdown($filename) {

	$rawContent = file_get_contents($filename);

	$output  = '';
	$output .= renderStructs($rawContent);
	$output .= renderClasses($rawContent);

	return $output;
}

function renderStructs($rawContent) {
	$output = '';
	$structs = readStructs($rawContent);
	if (count($structs) > 0) {
		$output .= '# ' . 'Structs' . "\n";
		foreach ($structs as $struct) {
			$output .= "\n" . markdownStruct($struct) . "\n";
		}
		$output .= '***' . "\n";
	}

	file_put_contents(OUTPUT_DIR . 'Structs.md', $output);

	return $output;
}

function renderClasses($rawContent) {
	$output = '';
	$rawClasses = readRawClasses($rawContent);

	$classes = [];
	foreach ($rawClasses as $rawClass) {
		$classes[] = readClass($rawClass);
	}

	if (count($classes) > 0) {
		foreach ($classes as $class) {
			$output .= "\n" . markdownClass($class) . "\n";
			file_put_contents(OUTPUT_DIR .  $class['name'] . '.md', $output);
		}
	}
	return $output;
}


function markdownStruct($struct) {
	$str  = '';
	$str .= '## ' . $struct['name'] . "\n";
	$str .= '```obj-c' . "\n";
	foreach ($struct['variables'] as $variable) {
		$str .= $variable['type'] . ' ' . $variable['name'] . "\n";
	}
	$str .= '```';
	return $str;
}

function markdownClass($class) {

	$str = '';
	$str .= '# ' . $class['name'] . "\n";
	foreach ($class['methods'] as $method) {
		$str  .= "\n```obj-c\n" . $method['code'] . "\n```\n";

		if (isset($method['description']['text'])) {
			$str .= "\n__Description__\n\n" . str_repeat('&nbsp;', 6) . $method['description']['text'] . "\n";
		}

		if (isset($method['description']['params'])) {
			if (count($method['description']['params']) > 0) {
				$str .= "\n__Parameters__\n";
			}
			foreach ($method['description']['params'] as $name => $param) {
				$str .=  "\n* __" . $name . '__ ' . $param . "\n";
			}
		}

		if (isset($method['description']['return'])) {
			$str .= "\n__Return__\n\n" . str_repeat('&nbsp;', 6) . $method['description']['return'] . "\n";
		}

		if (isset($method['description']['warning'])) {
			$str .= "\n__Warning__\n\n" . str_repeat('&nbsp;', 6) . $method['description']['warning'] . "\n";
		}
		$str .= "\n***\n";

	}
	return $str;

}



function readClass($rawClass) {
	$result = [];
	$rawMethods = readRawMethods($rawClass);
	$methods = [];
	foreach ($rawMethods as $rawMethod) {
		$methods[] = readMethod($rawMethod);
	}
	$lines = explode("\n", $rawClass);
	$code = $lines[0];


	$temp = explode(':', $code);
	$result['code'] = trim($code);
	$result['name'] = trim($temp[0]);
	$result['parent'] = trim($temp[1]);
	$result['methods'] = $methods;

	return $result;
}

function readMethod($rawMethod) {

	$lines = explode("\n", $rawMethod);
	for ($i=0; $i<count($lines); $i++) {
		if (strpos($lines[$i], '//') === 0) {
			unset($lines[$i]);
		}
	}
	$rawMethod = trim(implode("\n", $lines));


	$method['description'] = [];
	if (strpos($rawMethod, '/**') === 0) {
		$temps = explode('*/', $rawMethod);
		$description = str_replace("/**\n", '', $temps[0]);
		$method['description'] = readDescription($description);
		$method['code'] = trim($temps[1]);
	} else {
		$method['code'] = trim($rawMethod);
	}
	return $method;
}

function readDescription($description) {
	$lines = explode("\n", $description);
	$description = [];

	$result = [];

	$result['text'] = '';
	$params = []; 
	foreach ($lines as $line) {
		if(strpos($line, '@') === false) {
			$result['text'] .= $line . "\n";
		}

		if(strpos($line, '@param') === 0) {
			$temp = explode(' ', $line);
			$name = $temp[1];
			unset($temp[0]);
			unset($temp[1]);
			$params[$name] = implode(' ', $temp);
		}

		if(strpos($line, '@return') === 0) {
			$result['return'] = str_replace('@return ', '', $line);
		}

		if(strpos($line, '@warning') === 0) {
			$result['warning'] = str_replace('@warning ', '', $line);
		}

		if(strpos($line, '@property') === 0) {
			$result['property'] = str_replace('@property', '', $line);
		}
	}

	$result['text'] = trim($result['text']);
	$result['params'] = $params;
	return $result;
}

function readRawMethods($rawClass) {
	$lines = explode("\n", $rawClass);
	$rawMethods = [];
	$i = 0;

	// Get class name and remove it
	$name = $lines[0];
	$lines[0] = '';

	$endOfMethod = false;
	foreach ($lines as $line) {
		$line = trim($line);
		if (isset($rawMethods[$i])) {
			if ($endOfMethod) {
				$rawMethods[$i] .= ' ' . $line;
			} else {
				$rawMethods[$i] .= "\n" . $line;
			}
		} else {
			$rawMethods[$i] = $line;
		}

		if(strpos($line, '-') === 0 || strpos($line, '+') === 0 || strpos($line, '@property') === 0) {
			$rawMethods[$i] = trim($rawMethods[$i]);

			$endOfMethod = true;
		}
		if ($endOfMethod) {	
			if (strpos($line, ';') === strlen($line)-1) {
				$i++;
				$endOfMethod = false;
			}
		}
	}
	return $rawMethods;
}


function readRawClasses($rawContent) {
	$classes = [];
	$rawClasses = explode('@interface', $rawContent);
	foreach ($rawClasses as $rawClass) {
		$rawClass = subStrBetween($rawClass, 0, '@end');
		$rawClass = trim($rawClass);
		$lines = explode("\n", $rawClass);
		if (strpos($lines[0], ':') > 1) {
			$classes[] = $rawClass ;
		}
	}
	return $classes;
}

function readStructs($rawContent) {
	$structLines = explode('struct', $rawContent);

	$structs = [];

	foreach ($structLines as $structLine) {
		if (strpos(trim($structLine), '{') === 0 ) {
			$structLine = 'struct' . $structLine;
			$name = trim(subStrBetween($structLine, '}', ';'));
			$commands = explode(';', subStrBetween($structLine, '{', '}'));

			$struct = [];
			$struct['name'] = $name;
			$struct['variables'] = [];
			foreach ($commands as $command) {
				$command = trim($command);
				if (strlen($command) > 0) {
					$variable = readVariable($command);
					$struct['variables'][] = $variable;
				}
			}
			$structs[] = $struct;
		}
	}

	return $structs;
}

function readVariable($command) {
	$elements = explode(' ', $command);
	$result['name'] = $elements[1];
	$result['type'] = $elements[0];
	
	return $result;
}

function subStrBetween($string, $begin, $end){
	if($begin===0) {
		return substr($string, 0, strpos($string,$end));
	}
	if($end===0) {
		return substr($string, strpos($string, $begin)+strlen($begin));
	}

	$string = ' '.$string;
	$ini = strpos($string,$begin);
	if ($ini == 0) return '';
		$ini += strlen($begin);
	$len = strpos($string,$end,$ini) - $ini;
	return substr($string,$ini,$len);
}

function startsWith($haystack, $needle) {
    return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
function endsWith($haystack, $needle) {
    return $needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}

?>