<?php

abstract class Struct
{
    protected $buffer = array();

    public function __toString()
    {
        return implode(' ', $this->buffer);
    }

    public function size()
    {
        return count($this->buffer);
    }

    public function isEmpty()
    {
        return $this->size() == 0;
    }
}

class Stack extends Struct
{
    public function push($val)
    {
        $this->buffer[] = $val;
    }

    public function pop($cnt = null)
    {
        if (is_null($cnt)) {
            return array_pop($this->buffer);
        }

        $arg = array();
        while ($cnt--) {
            $arg[] = $this->pop();
        }
        return $arg;
    }

    public function top()
    {
        if ($this->size() == 0) {
            return null;
        }

        return $this->buffer[$this->size()-1];
    }
}

class Queue extends Struct
{
    public function enqueue($val)
    {
        $this->buffer[] = $val;
    }

    public function dequeue()
    {
        return array_shift($this->buffer);
    }
}

class Token
{
    public $type;
    public $value;

    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function __toString()
    {
        return (string)$this->value;
    }
}

class Number extends Token
{
    public function __construct($value)
    {
        $this->value = $this->normalize($value);
        $this->type = 'number';
    }

    private function normalize($value)
    {
        //$value = str_replace(',', '.', $value);
        return floatval($value);
    }
}

class Coma extends Token
{
    public function __construct($value)
    {
        parent::__construct('coma', $value);
    }
}

class Bracket extends Token
{
}

class L_bracket extends Bracket
{
    public function __construct($value)
    {
        parent::__construct('l_bracket', $value);
    }
}

class R_bracket extends Token
{
    public function __construct($value)
    {
        parent::__construct('r_bracket', $value);
    }
}

abstract class Operator extends Token
{
    public function __construct($value)
    {
        $this->value = $value;
        $this->type = 'operator';
    }

    abstract public function priority();

    abstract public function associativity();

    abstract public function execute($arg);

    public function numOfArgs()
    {
        return 2;
    }
}

class PlusOperator extends Operator
{
    public function priority()
    {
        return 2;
    }

    public function associativity()
    {
        return 'both';
    }

    public function execute($arg)
    {
        return new Number($arg[0]->value + $arg[1]->value);
    }
}

class MinusOperator extends Operator
{
    public function priority()
    {
        return 2;
    }

    public function associativity()
    {
        return 'left';
    }

    public function execute($arg)
    {
        return new Number($arg[0]->value - $arg[1]->value);
    }
}

class MultiplyOperator extends Operator
{
    public function priority()
    {
        return 3;
    }

    public function associativity()
    {
        return 'both';
    }

    public function execute($arg)
    {
        return new Number($arg[0]->value * $arg[1]->value);
    }
}

class DivideOperator extends Operator
{
    public function priority()
    {
        return 3;
    }

    public function associativity()
    {
        return 'left';
    }

    public function execute($arg)
    {
        if ($arg[1]->value == 0) {
            throw new Exception('Divide by zero');
        }
        return new Number($arg[0]->value / $arg[1]->value);
    }
}

class PowerOperator extends Operator
{
    public function priority()
    {
        return 4;
    }

    public function associativity()
    {
        return 'right';
    }

    public function execute($arg)
    {
        return new Number(pow($arg[0]->value, $arg[1]->value));
    }
}

abstract class Funct extends Token
{
    public function __construct($value)
    {
        $this->value = $value;
        $this->type = 'function';
    }

    abstract public function execute($arg);

    abstract public function numOfArgs();
}

class SinFunction extends Funct
{
    public function numOfArgs()
    {
        return 1;
    }

    public function execute($arg)
    {
        return new Number(sin($arg[0]->value));
    }
}

class CosFunction extends Funct
{
    public function numOfArgs()
    {
        return 1;
    }

    public function execute($arg)
    {
        return new Number(cos($arg[0]->value));
    }
}

class TgFunction extends Funct
{
    public function numOfArgs()
    {
        return 1;
    }

    public function execute($arg)
    {
        return new Number(tan($arg[0]->value));
    }
}

class CtgFunction extends Funct
{
    public function numOfArgs()
    {
        return 1;
    }

    public function execute($arg)
    {
        return new Number(tan(M_PI / 2 - $arg[0]->value));
    }
}

class MaxFunction extends Funct
{
    public function numOfArgs()
    {
        return 2;
    }

    public function execute($arg)
    {
        return new Number(max($arg[0]->value, $arg[1]->value));
    }
}

abstract class Constant extends Number
{
    public function __construct($value)
    {
        $this->value = $value;
        $this->type = 'constant';
    }

    public function execute()
    {
        return new Number($this->value);
    }
}

class PIConstant extends Constant
{
    public function __construct($value)
    {
        parent::__construct(M_PI);
    }
}

class EConstant extends Constant
{
    public function __construct($value)
    {
        parent::__construct(M_E);
    }
}

class Tokenizer implements Iterator
{
    private $expression;
    private $registeredTokens = array();
    private $tokenObjs = array();
    private $iPointer = 0;

    public function __construct($expr)
    {
        $this->expression = $expr;
        $this->expression = preg_replace('/\\s+/i', '$1', $this->expression);
        $this->expression = strtr($this->expression, '{}[]', '()()');
        if (empty($this->expression)) {
            throw new Exception('Expression to tokenize is empty');
        }

        $this->registerObject(null, 'number', '[\\d.]+');
        $this->registerObject(null, 'l_bracket', '\(');
        $this->registerObject(null, 'r_bracket', '\)');
        $this->registerObject(null, 'coma', '\,');

        $this->registerObject('operator', 'minus', '\-');
        $this->registerObject('operator', 'plus', '\+');
        $this->registerObject('operator', 'divide', '\/');
        $this->registerObject('operator', 'multiply', '\*');
        $this->registerObject('operator', 'power', '\^');

        $this->registerObject('constant', 'pi', 'PI');
        $this->registerObject('constant', 'e', 'E');

        $this->registerObject('function', 'sin', 'sin');
        $this->registerObject('function', 'cos', 'cos');
        $this->registerObject('function', 'tg', 'tg');
        $this->registerObject('function', 'ctg', 'ctg');
        $this->registerObject('function', 'max', 'max');
    }

    private function tokenFactory($token, $value)
    {
        if (!isset($this->registeredTokens[$token['type']])) {
            throw new Exception("Undefined token type '{$token['type']}'");
        }
        $className = $token['type'] . $token['classSuffix'];
        $obj = new $className($value);
        return $obj;
    }

    public function tokenize()
    {
        while (strlen($this->expression) > 0) {
            $isMatch = false;
            foreach ($this->registeredTokens as $token) {
                $regexp = "/^({$token['regexp']})/";

                if (!$isMatch && preg_match($regexp, $this->expression, $matches)) {
                    $isMatch = true;
                    $this->tokenObjs[] = $tokenObj = $this->tokenFactory($token, $matches[1]);
                    //echo "{$this->expression} -> {$tokenObj->type}: '{$tokenObj->value}'\n";
                    $this->expression = substr($this->expression, strlen($matches[1]));
                    break;
                }
            }
            if (!$isMatch) {
                throw new Exception("Unrecognized token: '{$this->expression}'");
            }
        }
    }

    public function registerObject($classSuffix, $type, $regexp)
    {
        $this->registeredTokens[$type] = array(
            'regexp' => $regexp,
            'type' => $type,
            'classSuffix' => $classSuffix,
        );
    }

    public function current()
    {
        return $this->tokenObjs[$this->iPointer];
    }

    public function key()
    {
        return $this->tokenObjs[$this->iPointer]->type;
    }

    public function next()
    {
        $this->iPointer++;
    }

    public function rewind()
    {
        $this->iPointer = 0;
    }

    public function valid()
    {
        return ($this->iPointer < sizeof($this->tokenObjs));
    }
}

class Calc
{
    private $expression;
    private $stack;
    private $rpnNotation;
    private $tokenizer;

    public function __construct($expr)
    {
        $this->expression = preg_replace('/\\s+/i', '$1', $expr);
        $this->expression = strtr($this->expression, '{}[]', '()()');
        if (empty($this->expression)) {
            throw new Exception('Expression to evaluate is empty');
        }
        $this->stack = new Stack();
        $this->rpnNotation = new Queue();
        $this->tokenizer = new Tokenizer($this->expression);

        //echo "Expression: {$this->expression}\n";
        //echo "-----------------------------\n";
    }

    /*
      http://en.wikipedia.org/wiki/Shunting-yard_algorithm
      http://pl.wikipedia.org/wiki/Odwrotna_notacja_polska
    */
    private function convertToRpn()
    {
        $this->tokenizer->tokenize();

        //echo "Converting to postfix notation:\n\n";

        foreach ($this->tokenizer as $token) {
            // Je�li symbol jest liczb�
            if ($token instanceof Number) {
                // dodaj go do kolejki wyj�cie
                $this->rpnNotation->enqueue($token);
            } // Je�li symbol jest funkcj�
            elseif ($token instanceof Funct) {
                // w�� go na stos.
                $this->stack->push($token);
            } // Je�li symbol jest znakiem oddzielaj�cym argumenty funkcji (np. przecinek):
            elseif ($token instanceof Coma) {
                // Dop�ki najwy�szy element stosu nie jest lewym nawiasem,
                $leftBracketExists = false;
                while (!($this->stack->top() instanceof L_bracket)) {
                    // zdejmij element ze stosu i dodaj go do kolejki wyj�cie.
                    $this->rpnNotation->enqueue($this->stack->pop());
                }
                // Je�li lewy nawias nie zosta� napotkany oznacza to,
                // �e znaki oddzielaj�ce zosta�y postawione w z�ym miejscu lub nawiasy s� �le umieszczone.
                if (!($this->stack->top() instanceof L_bracket)) {
                    throw new Exception('Missing left bracket in expression');
                }
            } // Je�li symbol jest operatorem, o1
            elseif ($token instanceof Operator) {
                // 1) dop�ki na g�rze stosu znajduje si� operator, o2 taki, �e:
                $stackTop = $this->stack->top();
                if (isset($stackTop) && $stackTop instanceof Operator) {
                    // o1 jest ��czny lub lewostronnie ��czny i jego kolejno�� wykonywania jest mniejsza
                    // lub r�wna kolejno�ci wyk. o2, lub
                    $test1 = (in_array($token->associativity(), array('both', 'left')))
                        && ($token->priority() <= $stackTop->priority());
                    //o1 jest prawostronnie ��czny i jego kolejno�� wykonywania jest mniejsza od o2,
                    $test2 = (in_array($token->associativity(), array('right')))
                        && ($token->priority() < $stackTop->priority());
                    if ($test1 || $test2) {
                        // zdejmij o2 ze stosu i do�� go do kolejki wyj�ciowej;
                        $this->rpnNotation->enqueue($this->stack->pop());
                    }
                }
                // 2) w�� o1 na stos operator�w.
                $this->stack->push($token);
            } // Je�eli symbol jest lewym nawiasem
            elseif ($token instanceof L_bracket) {
                // to w�� go na stos.
                $this->stack->push($token);
            } // Je�eli symbol jest prawym nawiasem
            elseif ($token instanceof R_bracket) {
                $leftBracketExists = false;
                while ($operator = $this->stack->pop()) {
                    // dop�ki symbol na g�rze stosu nie jest lewym nawiasem,
                    if ($operator instanceof L_bracket) {
                        $leftBracketExists = true;
                        break;
                    } // to zdejmuj operatory ze stosu i dok�adaj je do kolejki wyj�cie
                    else {
                        $this->rpnNotation->enqueue($operator);
                    }
                }

                // Teraz, je�li najwy�szy element na stosie jest funkcj�, tak�e do�� go do kolejki wyj�cie.
                if ($this->stack->top() instanceof Funct) {
                    $this->rpnNotation->enqueue($this->stack->pop());
                }

                // Je�li stos zostanie opr�niony i nie napotkasz lewego nawiasu, oznacza to,
                // �e nawiasy zosta�y �le umieszczone.
                if ($this->stack->isEmpty() && !$leftBracketExists) {
                    throw new Exception('Missing left bracket in expression');
                }
            }
        }
        // Je�li nie ma wi�cej symboli do przeczytania, zdejmuj wszystkie symbole ze stosu (je�li jakie� s�)
        // i dodawaj je do kolejki wyj�cia.
        while ($operator = $this->stack->pop()) {
            // Powinny to by� wy��cznie operatory,
            // je�li natrafisz na jaki� nawias, znaczy to, �e nawiasy zosta�y �le umieszczone.
            if ($operator instanceof Bracket) {
                throw new Exception('Mismatched brackets in expression');
            }

            $this->rpnNotation->enqueue($operator);
        }
    }

    private function process()
    {
        //echo "Processing postfix notation:\n\n";

        $tempStack = new Stack();

        while ($token = $this->rpnNotation->dequeue()) {
            if ($token instanceof Number) {
                $tempStack->push($token);
            } elseif (($token instanceof Operator) || ($token instanceof Funct)) {
                /** @var $token Operator|Funct */
                $arg = $tempStack->pop($token->numOfArgs());
                $tempStack->push($token->execute(array_reverse($arg)));
            }
        }
        return $tempStack->pop()->value;
    }

    public function evaluate()
    {
        $this->convertToRpn();
        return $this->process();
    }
}

/**
 * sin(PI/2+2*PI)+cos(2*2*PI)+tg(2*PI)+ctg(PI/2+2*PI)+2^2/2-2+2*2-4 + ((2+2)*2-(4/2*2)*2) =
 * 1 + 1 + 0 + 0 + 0
*/

$ret = null;
try {
    $calc = new Calc($_GET['expression']);
    $ret = $calc->evaluate();
} catch (Exception $e) {
    $ret = $e->getMessage();
}

echo json_encode(array('result' => $ret));

