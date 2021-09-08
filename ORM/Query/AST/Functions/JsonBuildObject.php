<?php

namespace Oro\Bundle\MailChimpBundle\ORM\Query\AST\Functions;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Implementation of json_build_object (PostgreSQL) and JSON_OBJECT (MySQL) for Doctrine ORM.
 */
class JsonBuildObject extends FunctionNode
{
    private array $parameters = [];

    /**
     * Parse JsonBuildObject(key, value[, key2, value2...])
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        // At least 2 parameter are required.
        $this->parameters[] = $parser->StringExpression();
        $parser->match(Lexer::T_COMMA);
        $this->parameters[] = $parser->StringExpression();

        // Other parameters are optional, but should have key, value pair.
        while ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $this->parameters[] = $parser->StringExpression();
            $parser->match(Lexer::T_COMMA);
            $this->parameters[] = $parser->StringExpression();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker)
    {
        $strings = [];
        $stringExpressions = $this->parameters;
        foreach ($stringExpressions as $stringExp) {
            $strings[] = $sqlWalker->walkStringPrimary($stringExp);
        }

        $platform = $sqlWalker->getConnection()->getDatabasePlatform();
        if ($platform instanceof PostgreSqlPlatform) {
            $function = 'json_build_object';
        } else {
            $function = 'JSON_OBJECT';
        }

        return sprintf(
            '%s(%s)',
            $function,
            \implode(', ', $strings)
        );
    }
}
