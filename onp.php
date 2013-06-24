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

        $this->tokenizer->registerObject(null, 'number', '[\\d.]+');
        $this->tokenizer->registerObject(null, 'l_bracket', '\(');
        $this->tokenizer->registerObject(null, 'r_bracket', '\)');
        $this->tokenizer->registerObject(null, 'coma', '\,');

        $this->tokenizer->registerObject('operator', 'minus', '\-');
        $this->tokenizer->registerObject('operator', 'plus', '\+');
        $this->tokenizer->registerObject('operator', 'divide', '\/');
        $this->tokenizer->registerObject('operator', 'multiply', '\*');
        $this->tokenizer->registerObject('operator', 'power', '\^');

        $this->tokenizer->registerObject('constant', 'pi', 'PI');
        $this->tokenizer->registerObject('constant', 'e', 'E');

        $this->tokenizer->registerObject('function', 'sin', 'sin');
        $this->tokenizer->registerObject('function', 'cos', 'cos');
        $this->tokenizer->registerObject('function', 'tg', 'tg');
        $this->tokenizer->registerObject('function', 'ctg', 'ctg');
        $this->tokenizer->registerObject('function', 'max', 'max');

        //echo "Expression: {$this->expression}\n";
        //echo "-----------------------------\n";
    }

    /**
     * @link http://en.wikipedia.org/wiki/Shunting-yard_algorithm
     * @link http://pl.wikipedia.org/wiki/Odwrotna_notacja_polska
     */
    private function convertToRpn()
    {
        $this->tokenizer->tokenize();

        //echo "Converting to postfix notation:\n\n";

        foreach ($this->tokenizer as $token) {
            // Jeśli symbol jest liczbą
            if ($token instanceof Number) {
                // dodaj go do kolejki wyjście
                $this->rpnNotation->enqueue($token);
            } // Jeśli symbol jest funkcją
            elseif ($token instanceof Funct) {
                // włóż go na stos.
                $this->stack->push($token);
            } // Jeśli symbol jest znakiem oddzielającym argumenty funkcji (np. przecinek):
            elseif ($token instanceof Coma) {
                // Dopóki najwyższy element stosu nie jest lewym nawiasem,
                $leftBracketExists = false;
                while (!($this->stack->top() instanceof L_bracket)) {
                    // zdejmij element ze stosu i dodaj go do kolejki wyjście.
                    $this->rpnNotation->enqueue($this->stack->pop());
                }
                // Jeśli lewy nawias nie został napotkany oznacza to,
                // że znaki oddzielające zostały postawione w złym miejscu lub nawiasy są źle umieszczone.
                if (!($this->stack->top() instanceof L_bracket)) {
                    throw new Exception('Missing left bracket in expression');
                }
            } // Jeśli symbol jest operatorem, o1
            elseif ($token instanceof Operator) {
                // 1) dopóki na górze stosu znajduje się operator, o2 taki, że:
                $stackTop = $this->stack->top();
                if (isset($stackTop) && $stackTop instanceof Operator) {
                    // o1 jest łączny lub lewostronnie łączny i jego kolejność wykonywania jest mniejsza
                    // lub równa kolejności wyk. o2, lub
                    $test1 = (in_array($token->associativity(), array('both', 'left')))
                        && ($token->priority() <= $stackTop->priority());
                    //o1 jest prawostronnie łączny i jego kolejność wykonywania jest mniejsza od o2,
                    $test2 = (in_array($token->associativity(), array('right')))
                        && ($token->priority() < $stackTop->priority());
                    if ($test1 || $test2) {
                        // zdejmij o2 ze stosu i dołóż go do kolejki wyjściowej;
                        $this->rpnNotation->enqueue($this->stack->pop());
                    }
                }
                // 2) włóż o1 na stos operatorów.
                $this->stack->push($token);
            } // Jeśeli symbol jest lewym nawiasem
            elseif ($token instanceof L_bracket) {
                // to włóż go na stos.
                $this->stack->push($token);
            } // Jeśeli symbol jest prawym nawiasem
            elseif ($token instanceof R_bracket) {
                $leftBracketExists = false;
                while ($operator = $this->stack->pop()) {
                    // dopóki symbol na górze stosu nie jest lewym nawiasem,
                    if ($operator instanceof L_bracket) {
                        $leftBracketExists = true;
                        break;
                    } // to zdejmuj operatory ze stosu i dokładaj je do kolejki wyjście
                    else {
                        $this->rpnNotation->enqueue($operator);
                    }
                }

                // Teraz, jeśli najwyższy element na stosie jest funkcją, także dołóż go do kolejki wyjście.
                if ($this->stack->top() instanceof Funct) {
                    $this->rpnNotation->enqueue($this->stack->pop());
                }

                // Jeśli stos zostanie opróżniony i nie napotkasz lewego nawiasu, oznacza to,
                // że nawiasy zostały źle umieszczone.
                if ($this->stack->isEmpty() && !$leftBracketExists) {
                    throw new Exception('Missing left bracket in expression');
                }
            }
        }
        // Jeśli nie ma więcej symboli do przeczytania, zdejmuj wszystkie symbole ze stosu (jeśli jakieś są)
        // i dodawaj je do kolejki wyjścia.
        while ($operator = $this->stack->pop()) {
            // Powinny to być wyłącznie operatory,
            // jeśli natrafisz na jakiś nawias, znaczy to, że nawiasy zostały źle umieszczone.
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

