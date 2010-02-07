<?php

    /* $Id$ */ 
    
    class ErrorReporter { 
        /**
        * @var const
        */
        const CR = "\n";
        
        public static function errorHandler($c, $m, $f, $l) {
            /**
            * Проверяем значение error_reporting(). Если это значение равно 0, то ошибка
            * произошла из-за выражения, перед которым стоит оператор "@" (подавление
            * сообщений об ошибках). В таком случае игнорируем эту ошибку.
            * 
            * @see http://php.net/set_error_handler
            */
            if (0 === error_reporting()) return;
            
            self::displayError($m, $c, $f, $l, 2);
        }

        public static function exceptionHandler(Exception $e) {
            $message = $e->getMessage();
            $code    = $e->getCode();
            $file    = $e->getFile();
            $line    = $e->getLine();
            $trace   = $e->getTrace();
            
            self::displayError($message, $code, $file, $line, 0, get_class($e), $trace);
        }

        private static function displayError( $message, $code, $file, $line, $skip_backtrace = 1, $exception = false, $trace = null ) {
            $trace = (!$exception ? debug_backtrace() : $trace);
              
            if ($exception)
                $header = 'Exception';
            else
                switch ($code) {
                    case 1:
                    case 16:     $header = 'PHP Error'; break;
                    case 2:
                    case 32:     $header = 'PHP Warning'; break;
                    case 8:      $header = 'PHP Notice'; break;

                    case 256:    $header = 'User Error'; break;
                    case 512:    $header = 'User Warning'; break;
                    case 1024:   $header = 'User Notice'; break;

                    default:     $header = 'Error';
                }

            // Clean output buffer
            while ( ob_get_level() !== 0 ) { ob_end_clean(); }
            
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $header ?></title>
<style type="text/css" media="screen">
    html {margin:0;padding:0;background:#BEC6C6;}
    body {width:50em; margin:2em auto;border:4px solid #eee; padding:0.5em 1em 1.5em; font-size:14px; font-family: Verdana, sans-serif; background-color:#fffafa}
    h1,h2 {color:#639cb1; border-bottom:1px solid #d7e1e1; text-transform:uppercase; font-family: Arial, sans-serif;font-weight:100;letter-spacing:2px}
    h1 {font-size: 22px;padding:0.159em 0;margin:0.159em 0;}
    h2 {font-size: 14px;padding:0.25em 0;margin:0.25em 0}
    .backtrace, .backtrace li {margin:0;padding:0;list-style:none}
    .line,.path {font-family:monospace}
    .backtrace .path {display:block;font-size:14px;margin-top:1em; padding:0 0 0.25em; color:#7f7a79}
    .code-block {padding-left:3.5em;border:1px solid #b0a8a6;border-width:1px 0;font-size:12px;font-family:monospace;line-height:1.4;background:#ddd url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAIAAAAmkwkpAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAgY0hSTQAAeiYAAICEAAD6AAAAgOgAAHUwAADqYAAAOpgAABdwnLpRPAAAABh0RVh0U29mdHdhcmUAUGFpbnQuTkVUIHYzLjMxN4N3hgAAACJJREFUGFdjfPr0KQMYvHv3jgHIAYLLly+DROEsEAciBgEAX8klDXI0GZkAAAAASUVORK5CYII=')}
    .code-block .line {float:left;width:3em;margin-left:-3.375em;padding:0.25em 0;text-align:right}
    .code-block code {display:block;background-color:#eee;padding:0.25em;}
</style>
</head>
<body>
<h1><?php echo $header ?></h1>
<p><?php echo ($exception ? $exception . ' thrown' : 'An error encountered'); ?> in <span class="path"><?php echo $file ?></span> on line <span class="line"><?php echo $line ?>.</span></p>
<h2>Error Message</h2>
<p><?php echo $message ?>.</p>
<?php 

$show_backtrace = true;

if ( $show_backtrace && count($trace) > $skip_backtrace ):
$trace = array_slice($trace, $skip_backtrace);
?>
<h2>Stack backtrace</h2>
<ol class="backtrace">
<?php    foreach ( $trace as $line ): ?>
<?php     if ( isset($line['function']) && $line['function'] == 'trigger_error' ) continue; ?>
    <li>
        <span class="path"><?php echo isset($line['file']) ? $line['file'] .':' : 'PHP inner process:' ?></span>
        <div class="code-block">
        <?php
            echo '<span class="line">' . ( isset($line['line']) ? $line['line'] . '.' : '') . '</span>';

            $function = isset($line['function']) ? $line['function'] : '';

            $code = '<?php' . (isset($line['class']) ? $line['class'] : '') .
                (isset($line['type']) ? $line['type'] : '') . $function;

            if ( isset($line['args']) )
            {
                $args = array();

                foreach ( $line['args'] as $arg )
                {
                    if ( is_string($arg) )
                        $args[] = '"' . $arg . '"';
                    else if ( is_array($arg) )
                        $args[] = print_r($arg, true);
                    else if ( is_bool($arg) )
                        $args[] = $arg === true ? 'true' : 'false';
                    else
                        $args[] = $arg;
                }

                $code.= '(' . implode(', ', $args) . '); ?>';
            }

            $code = wordwrap($code, 75, self::CR, true);
            $code = highlight_string($code, true);
            $code = str_replace(array('&lt;?php','?&gt;'),'',$code);

            echo $code;
    ?>
    </div></li>
<?php    endforeach ?>
</ol>
<?php endif ?>
</body>
</html>
<?php
            exit;
        }
    }
    
?>