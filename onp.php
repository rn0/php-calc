<?php

abstract class Struct {
  protected $buffer = array();
  
  public function __toString() {
    return implode(' ', $this->buffer);
  }
  
  public function size() {
    return count($this->buffer);
  }
  
  public function isEmpty() {
    return $this->size() == 0;
  }
}

class Stack extends Struct {
  public function push($val) {
    $this->buffer[] = $val; 
  }
  
  public function pop($cnt = null) {
    if(is_null($cnt)) {
      return array_pop($this->buffer);
    }
    elseif(is_numeric($cnt)) {
      $arg = array();
      while($cnt--) {
        $arg[] = $this->pop();
      }
      return $arg;
    }
  }
  
  public function top() {
    // FIXIT:
    return $this->buffer[$this->size()-1];
  }
}

class Queue extends Struct {
  public function enqueue($val) {
    $this->buffer[] = $val; 
  }
  
  public function dequeue() {
    return array_shift($this->buffer);
  }
}

class Token {
  public $type;
  public $value;
  
  public function __construct($type, $value) {
    $this->type = $type;
    $this->value = $value;
  }
  
  public function __toString() {
    return (string)$this->value;
  }
}

class Number extends Token {
  public function __construct($value) {
    $this->value = $this->normalize($value);
    $this->type = 'number';
  }
  private function normalize($value) {
    //$value = str_replace(',', '.', $value);
    return floatval($value);
  }
}
class Coma extends Token {
  public function __construct($value) {
    parent::__construct('coma', $value);
  }
}
class Bracket extends Token {
}
class L_bracket extends Bracket {
  public function __construct($value) {
    parent::__construct('l_bracket', $value);
  }
}
class R_bracket extends Token {
  public function __construct($value) {
    parent::__construct('r_bracket', $value);
  }
}

abstract class Operator extends Token {
  public function __construct($value) { 
    $this->value = $value;
    $this->type = 'operator';
  }
  
  abstract function priority();
  abstract function associativity();
  abstract function execute($arg);
  public function numOfArgs() {
    return 2;
  }
}

class PlusOperator extends Operator {
  public function priority() {
    return 2;
  }
  public function associativity() {
    return 'both';
  }
  public function execute($arg) {
    return new Number($arg[0]->value + $arg[1]->value);
  }
}
class MinusOperator extends Operator {
  public function priority() {
    return 2;
  }
  public function associativity() {
    return 'left';
  }
  public function execute($arg) {
    return new Number($arg[0]->value - $arg[1]->value);
  }
}
class MultiplyOperator extends Operator {
  public function priority() {
    return 3;
  }
  public function associativity() {
    return 'both';
  }
  public function execute($arg) {
    return new Number($arg[0]->value * $arg[1]->value);
  }
}
class DivideOperator extends Operator {
  public function priority() {
    return 3;
  }
  public function associativity() {
    return 'left';
  }
  public function execute($arg) {
    if($arg[1]->value == 0) {
      throw new Exception('Divide by zero');
    }
    return new Number($arg[0]->value / $arg[1]->value);
  }
}
class PowerOperator extends Operator {
  public function priority() {
    return 4;
  }
  public function associativity() {
    return 'right';
  }
  public function execute($arg) {
    return new Number(pow($arg[0]->value, $arg[1]->value));
  }
}

abstract class Funct extends Token {
  public function __construct($value) { 
    $this->value = $value;
    $this->type = 'function';
  }
  abstract function execute($arg);
  abstract function numOfArgs();
}
class SinFunction extends Funct {
  public function numOfArgs() {
    return 1;
  }
  public function execute($arg) {
    return new Number(sin($arg[0]->value));
  }
}
class CosFunction extends Funct {
  public function numOfArgs() {
    return 1;
  }
  public function execute($arg) {
    return new Number(cos($arg[0]->value));
  }
}
class TgFunction extends Funct {
  public function numOfArgs() {
    return 1;
  }
  public function execute($arg) {
    return new Number(tan($arg[0]->value));
  }
}
class CtgFunction extends Funct {
  public function numOfArgs() {
    return 1;
  }
  public function execute($arg) {
    return new Number(tan(M_PI/2 - $arg[0]->value));
  }
}
class MaxFunction extends Funct {
  public function numOfArgs() {
    return 2;
  }
  public function execute($arg) {
    return new Number(max($arg[0]->value, $arg[1]->value));
  }
}



abstract class Constant extends Number {
  public function __construct($value) { 
    $this->value = $value;
    $this->type = 'constant';
  }
  public function execute() {
    return new Number($this->value);
  }
}
class PIConstant extends Constant {
  public function __construct($value) {
    parent::__construct(M_PI);
  }
}
class EConstant extends Constant {
  public function __construct($value) {
    parent::__construct(M_E);
  }
}

class Tokenizer implements Iterator {
  private $expression;
  private $registeredTokens = array();
  private $tokenObjs = array();
  private $iPointer = 0;
  
  public function __construct($expr) {
    $this->expression = $expr;
    $this->expression = preg_replace('/\\s+/i', '$1', $this->expression);
    $this->expression = strtr($this->expression, '{}[]', '()()');
    if(empty($this->expression)) {
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
  
  private function tokenFactory($token, $value) {
    if(!isset($this->registeredTokens[$token['type']])) {
      throw new Exception("Undefined token type '{$token['type']}'");
    }
    $className = $token['type'].$token['classSuffix'];
    $obj = new $className($value);
    return $obj;
  }
  
  public function tokenize() {
    //echo "Tokenizing expression: \n\n";
    while(strlen($this->expression) > 0) {
      $isMatch = false;
      foreach($this->registeredTokens as $type => $token) {
        $regexp = "/^({$token['regexp']})/";
        
        if(!$isMatch && preg_match($regexp, $this->expression, $matches)) {
          $isMatch = true;
          $this->tokenObjs[] = $tokenObj = $this->tokenFactory($token, $matches[1]);
          //echo "{$this->expression} -> {$tokenObj->type}: '{$tokenObj->value}'\n";
          $this->expression = substr($this->expression, strlen($matches[1]));
          break;
        }
      }
      if(!$isMatch) {
        throw new Exception("Unrecognized token: '{$this->expression}'");
      }
    }
    //echo "\nTokens: " . implode('|', $this->tokenObjs) . "\n";
    //echo "-----------------------------\n";
  }
  
  public function registerObject($classSuffix, $type, $regexp) {
    $this->registeredTokens[$type] = array(
      'regexp' => $regexp,
      'type'   => $type,
      'classSuffix' => $classSuffix,
    );
  }

  function current() {
    return $this->tokenObjs[$this->iPointer];
  }
  function key() {
    return $this->tokenObjs[$this->iPointer]->type;
  }
  function next() {
    $this->iPointer++;
  }
  function rewind() {
    $this->iPointer = 0;
  }
  function valid() {
    return ($this->iPointer < sizeof($this->tokenObjs));
  }
}

class Calc {
  private $expression, $stack, $rpnNotation, $tokenizer;
  
  public function __construct($expr) {
    $this->expression = preg_replace('/\\s+/i', '$1', $expr);
    $this->expression = strtr($this->expression, '{}[]', '()()');
    if(empty($this->expression)) {
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
  private function convertToRPN() {
    $this->tokenizer->tokenize();
    
    //echo "Converting to postfix notation:\n\n";
    
    foreach($this->tokenizer as $token) {
      // Jeœli symbol jest liczb¹
      if($token instanceof Number) {
        // dodaj go do kolejki wyjœcie
        $this->rpnNotation->enqueue($token);
      }
      // Jeœli symbol jest funkcj¹
      elseif($token instanceof Funct) {
        // w³ó¿ go na stos.
        $this->stack->push($token);
      }
      // Jeœli symbol jest znakiem oddzielaj¹cym argumenty funkcji (np. przecinek):
      elseif($token instanceof Coma) {
        // Dopóki najwy¿szy element stosu nie jest lewym nawiasem,
        $leftBracketExists = false;
        while(!($this->stack->top() instanceof L_bracket)) {
          // zdejmij element ze stosu i dodaj go do kolejki wyjœcie.
          $this->rpnNotation->enqueue($this->stack->pop());
        }
        // Jeœli lewy nawias nie zosta³ napotkany oznacza to, 
        // ¿e znaki oddzielaj¹ce zosta³y postawione w z³ym miejscu lub nawiasy s¹ Ÿle umieszczone.
        if(!($this->stack->top() instanceof L_bracket)) {
          throw new Exception('Missing left bracket in expression');
        }
      }
      // Jeœli symbol jest operatorem, o1
      elseif($token instanceof Operator) {
        // 1) dopóki na górze stosu znajduje siê operator, o2 taki, ¿e:
        $stackTop = $this->stack->top();
        if(isset($stackTop) && $stackTop instanceof Operator) {
          //o1 jest ³¹czny lub lewostronnie ³¹czny i jego kolejnoœæ wykonywania jest mniejsza lub równa kolejnoœci wyk. o2, lub
          $test1 = (in_array($token->associativity(), array('both', 'left'))) && ($token->priority() <= $stackTop->priority());
          //o1 jest prawostronnie ³¹czny i jego kolejnoœæ wykonywania jest mniejsza od o2,
          $test2 = (in_array($token->associativity(), array('right'))) && ($token->priority() < $stackTop->priority());
          if($test1 || $test2) {
            // zdejmij o2 ze stosu i do³ó¿ go do kolejki wyjœciowej;
            $this->rpnNotation->enqueue($this->stack->pop());
          }
        }
        // 2) w³ó¿ o1 na stos operatorów.
        $this->stack->push($token);
      }
      // Je¿eli symbol jest lewym nawiasem
      elseif($token instanceof L_bracket) {
        // to w³ó¿ go na stos.
        $this->stack->push($token);
      }
      // Je¿eli symbol jest prawym nawiasem
      elseif($token instanceof R_bracket) {
        $leftBracketExists = false;
        while($operator = $this->stack->pop()) {
          // dopóki symbol na górze stosu nie jest lewym nawiasem,
          if($operator instanceof L_bracket) {
            $leftBracketExists = true;
            break;
          }
          // to zdejmuj operatory ze stosu i dok³adaj je do kolejki wyjœcie
          else {
            $this->rpnNotation->enqueue($operator);
          }
        }
        
        // Teraz, jeœli najwy¿szy element na stosie jest funkcj¹, tak¿e do³ó¿ go do kolejki wyjœcie.
        if($this->stack->top() instanceof Funct) {
          $this->rpnNotation->enqueue($this->stack->pop());
        }
        
        // Jeœli stos zostanie opró¿niony i nie napotkasz lewego nawiasu, oznacza to, ¿e nawiasy zosta³y Ÿle umieszczone.
        if($this->stack->isEmpty() && !$leftBracketExists) {
          throw new Exception('Missing left bracket in expression');
        }
      }
      $class = get_class($token);  
      //echo "Token: {$token}\tType: {$token->type}\tClass: {$class}\t\tStack: '{$this->stack}'\t\tOut: '{$this->rpnNotation}'\n";
    }
    // Jeœli nie ma wiêcej symboli do przeczytania, zdejmuj wszystkie symbole ze stosu (jeœli jakieœ s¹) i dodawaj je do kolejki wyjœcia.
    while($operator = $this->stack->pop()) {
      // Powinny to byæ wy³¹cznie operatory, 
      // jeœli natrafisz na jakiœ nawias, znaczy to, ¿e nawiasy zosta³y Ÿle umieszczone.
      if($operator instanceof Bracket) {
        throw new Exception('Mismatched brackets in expression');
      }
      
      $this->rpnNotation->enqueue($operator);
    }
    //echo "Token: null\tType: null\tClass: null\t\tStack: '{$this->stack}'\t\tOut: '{$this->rpnNotation}'\n";
    //echo "\n\nPostfix notation: {$this->rpnNotation}\n";
    //echo "-----------------------------\n";
  }
  
  private function process() {
    //echo "Processing postfix notation:\n\n";
    
    $tempStack = new Stack();
    
    while($token = $this->rpnNotation->dequeue()) {
      if($token instanceof Number) {
        $tempStack->push($token);
      }
      elseif(($token instanceof Operator) || ($token instanceof Funct)) {
        $arg = $tempStack->pop($token->numOfArgs());
        $tempStack->push($token->execute(array_reverse($arg)));
        
        $class = get_class($token); 
        //echo "Executing {$class}\t Return: {$tempStack->top()->value}\n";
      }
    }
    return $tempStack->pop()->value;
  }
  
  public function evaluate() {
    $this->convertToRPN();
    return $this->process();
  }
}

/*echo '<pre style="font-size: xx-small;">';

$expr = 'sin(PI/2+2*PI)+cos(2*2*PI)+tg(2*PI)+ctg(PI/2+2*PI)+2^2/2-2+2*2-4 + ((2+2)*2-(4/2*2)*2)';
// 1 + 1 + 0 + 0 + 0

$expr = "max({$expr}, max(1, 0))";

$calc = new Calc($expr);
$ret = $calc->evaluate();
echo "\nWynik: {$ret}";*/
$ret = null;
try {
  $calc = new Calc($_GET['expression']);
  $ret = $calc->evaluate();
}
catch(Exception $e) {
  $ret = $e->getMessage();
}
echo json_encode(array('result' => $ret));