<?php

use Twig\Compiler;
use Twig\Token;

// These files are loaded three times and we can't re-set a class.
if (!class_exists("Project_grid_Node", FALSE)) {

  class Project_grid_Node extends \Twig\Node\Node {

    /**
     * The class to compile.
     *
     * @var string
     */
    private $class = "";

    /**
     * Sets the class.
     *
     * @param string $class
     *   The class to set.
     */
    public function setClass($class) {
      $this->class = $class;
    }

    /**
     * {@inheritDoc}
     */
    public function compile(Compiler $compiler) {
      $compiler->addDebugInfo($this);
      $compiler->write("echo \"<div class='o-grid ");

      $class = " ";
      if ($this->class instanceof \Twig\Node\Expression\ConstantExpression) {
        $class = preg_replace("/\b(lg|md|sm|xs)([0-9]+)\b/", 'col-$1-$2', $this->class->getAttribute("value"));
      }
      $compiler->raw($class);

      $compiler->raw("'>\";")->raw(PHP_EOL);
      parent::compile($compiler);
      $compiler->write("echo \"</div>\";")->raw(PHP_EOL);

    }

  }

}

// These files are loaded three times and we can't re-set a class.
if (!class_exists("Project_grid_TokenParser", FALSE)) {

  class Project_grid_TokenParser extends \Twig\TokenParser\AbstractTokenParser {

    /**
     * {@inheritDoc}
     */
    public function parse(Token $token) {
      $inheritanceIndex = 1;

      $stream = $this->parser->getStream();

      $nodes = [];
      $classes = [];
      $returnNode = NULL;


      if ($stream->test(\Twig\Token::STRING_TYPE)) {
        $classes[$inheritanceIndex] = $this->parser->getExpressionParser()
          ->parseStringExpression();
      }
      else {
        $classes[$inheritanceIndex] = "col-md-12";
      }

      $stream->expect(\Twig\Token::BLOCK_END_TYPE);
      $continue = TRUE;


      while ($continue) {

        $content = $this->parser->subparse([$this, 'decideMyTagFork']);
        $nodes[$inheritanceIndex][] = $content;
        $tag = $stream->next()->getValue();
        switch ($tag) {
          case "grid":
            $inheritanceIndex++;
            if ($stream->test(\Twig\Token::STRING_TYPE)) {
              $classes[$inheritanceIndex] = $this->parser->getExpressionParser()
                ->parseStringExpression();
            }
            else {
              $classes[$inheritanceIndex] = "col-md-12";
            }
            break;

          case "endgrid":
            $currentNodes = $nodes[$inheritanceIndex];
            $class = $classes[$inheritanceIndex];
            unset($nodes[$inheritanceIndex]);
            unset($classes[$inheritanceIndex]);
            $inheritanceIndex--;
            if ($inheritanceIndex == 0) {
              $returnNode = new Project_grid_Node($currentNodes);
              $returnNode->setClass($class);
              $continue = FALSE;
            }
            else {
              $node = new Project_grid_Node($currentNodes);
              $node->setClass($class);
              $nodes[$inheritanceIndex][] = $node;
            }
            break;

          default:
            throw new \Twig\Error\SyntaxErro(sprintf('Unexpected end of template. Twig was looking for the following tags "%s" to close the "%s" block started at line %d)', "endgrid", "grid", $this->startLine), -1);
        }
        $stream->expect(\Twig\Token::BLOCK_END_TYPE);
      }

      return $returnNode;
    }

    /**
     * {@inheritDoc}
     */
    public function getTag() {
      return "grid";
    }

    /**
     * Decide tag fork.
     *
     * Callback called at each tag name when subparsing, must return
     * true when the expected end tag is reached.
     *
     * @param \Twig\Token $token
     *   The token in question.
     *
     * @return bool
     *   Returns true expected end tag is reached.
     */
    public function decideMyTagFork(Token $token) {
      return $token->test(["grid", "endgrid"]);
    }

  }

}
