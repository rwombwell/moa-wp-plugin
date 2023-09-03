<?php
/***************
 * Based on GPT answer for a recursive descent parser  - NOT USED YET
 * Supports functions
 * Has Formal Grammar
 *        Expression   -> AndExpr OR Expression | AndExpr
 *        AndExpr      -> NotExpr AND AndExpr | NotExpr
 *        NotExpr      -> NOT Atom | Atom
 *        Atom         -> TERM | ( Expression )
 *        TERM         -> sequence of characters (a word or phrase)
 ****************/
/*
 class SearchParser {
    private $tokens;
    private $current = 0;

    public function parse($input) {
        $this->tokens = preg_split('/\s+/', $input);
        return $this->expression();
    }

    private function expression() {
        $expr = $this->andExpression();

        while ($this->match('OR')) {
            $right = $this->andExpression();
            $expr = [
                'bool' => [
                    'should' => [$expr, $right]
                ]
            ];
        }

        return $expr;
    }

    private function andExpression() {
        $expr = $this->notExpression();

        while ($this->match('AND')) {
            $right = $this->notExpression();
            $expr = [
                'bool' => [
                    'must' => [$expr, $right]
                ]
            ];
        }

        return $expr;
    }

    private function notExpression() {
        if ($this->match('NOT')) {
            $right = $this->atom();
            return [
                'bool' => [
                    'must_not' => $right
                ]
            ];
        }

        return $this->atom();
    }

    private function atom() {
        if ($this->match('(')) {
            $expr = $this->expression();
            $this->consume(')');
            return $expr;
        }

        $token = $this->consume(null);
        return [
            'match' => [
                'content' => $token  // assuming 'content' is the field name in ES
            ]
        ];
    }

    private function match($type) {
        if ($this->isAtEnd()) return false;
        return $this->tokens[$this->current] == $type;
    }

    private function consume($type) {
        if ($type && !$this->match($type)) {
            throw new Exception("Expected $type, got " . $this->tokens[$this->current]);
        }
        return $this->tokens[$this->current++];
    }

    private function isAtEnd() {
        return $this->current >= count($this->tokens);
    }
}

$parser = new SearchParser();
$query = $parser->parse("apple AND (banana OR cherry) NOT grape");
print_r($query);
*/

/****************************
 * 2nd stab at Compount ES DSL parser for AND, OR, NOT, WIldcards and Phrases, this function is NOT Recursive, in ans to query
 * "Give me php code to create elasticsearch dsl to support compound query with and, or, not, wildcards and phrases"
 ***************************/
/*
function buildCompoundQuery($query) {
    $dsl = [
        'query' => [
            'bool' => [
                'must' => [],
                'should' => [],
                'must_not' => []
            ]
        ]
    ];

    // AND condition
    foreach ($query['and'] as $field => $value) {
        $dsl['query']['bool']['must'][] = [
            'match' => [
                $field => $value
            ]
        ];
    }

    // OR condition
    foreach ($query['or'] as $field => $value) {
        $dsl['query']['bool']['should'][] = [
            'match' => [
                $field => $value
            ]
        ];
    }

    // NOT condition
    foreach ($query['not'] as $field => $value) {
        $dsl['query']['bool']['must_not'][] = [
            'match' => [
                $field => $value
            ]
        ];
    }

    // Wildcards
    foreach ($query['wildcard'] as $field => $value) {
        $dsl['query']['bool']['must'][] = [
            'wildcard' => [
                $field => '*' . $value . '*'
            ]
        ];
    }

    // Phrases
    foreach ($query['phrase'] as $field => $value) {
        $dsl['query']['bool']['must'][] = [
            'match_phrase' => [
                $field => $value
            ]
        ];
    }

    return json_encode($dsl, JSON_PRETTY_PRINT);
}

// Example usage:
$query = [
    'and' => [
        'field1' => 'value1',
        'field2' => 'value2'
    ],
    'or' => [
        'field3' => 'value3',
        'field4' => 'value4'
    ],
    'not' => [
        'field5' => 'value5'
    ],
    'wildcard' => [
        'field6' => 'val*'
    ],
    'phrase' => [
        'field7' => 'quick brown fox'
    ]
];

echo buildCompoundQuery($query);
*/

/********************************
 * 3rd stab at Query conversion into ES DSL, this one extends the above for AND, OR, NOT, Wildcard and Phrases, adding recursive descent
 * This is the  grammar:
    QUERY      -> AND | OR | NOT | WILDCARD | PHRASE
    AND        -> "AND(" FIELD "," VALUE ")"
    OR         -> "OR(" FIELD "," VALUE ")"
    NOT        -> "NOT(" FIELD "," VALUE ")"
    WILDCARD   -> "WILDCARD(" FIELD "," VALUE ")"
    PHRASE     -> "PHRASE(" FIELD "," PHRASEVALUE ")"
    FIELD      -> string
    VALUE      -> string
    PHRASEVALUE -> string containing spaces
 * This parser accepts input in reverse polish like "AND(field1,value1) OR(field2,value2) NOT(field3,value3)" and parses it into the PHP associative array. 
 * Adjustments can be made to further refine or expand the grammar.
 *******************************/
/*
class Parser {
    private $tokens;
    private $index;

    public function __construct($input) {
        $this->tokens = preg_split("/[\s,()]+/", $input);
        $this->index = 0;
    }

    public function parse() {
        $result = $this->parseQUERY();
        if ($this->index < count($this->tokens)) {
            throw new Exception("Unexpected token: " . $this->tokens[$this->index]);
        }
        return $result;
    }

    private function parseQUERY() {
        if ($this->tokens[$this->index] === 'AND') {
            $this->index++;
            $field = $this->parseFIELD();
            $value = $this->parseVALUE();
            return [
                'and' => [$field => $value]
            ];
        } elseif ($this->tokens[$this->index] === 'OR') {
            $this->index++;
            $field = $this->parseFIELD();
            $value = $this->parseVALUE();
            return [
                'or' => [$field => $value]
            ];
        } elseif ($this->tokens[$this->index] === 'NOT') {
            $this->index++;
            $field = $this->parseFIELD();
            $value = $this->parseVALUE();
            return [
                'not' => [$field => $value]
            ];
        } elseif ($this->tokens[$this->index] === 'WILDCARD') {
            $this->index++;
            $field = $this->parseFIELD();
            $value = $this->parseVALUE();
            return [
                'wildcard' => [$field => $value]
            ];
        } elseif ($this->tokens[$this->index] === 'PHRASE') {
            $this->index++;
            $field = $this->parseFIELD();
            $value = $this->parsePHRASEVALUE();
            return [
                'phrase' => [$field => $value]
            ];
        }
        throw new Exception("Unexpected token: " . $this->tokens[$this->index]);
    }

    private function parseFIELD() {
        $token = $this->tokens[$this->index];
        $this->index++;
        return $token;
    }

    private function parseVALUE() {
        return $this->parseFIELD();
    }

    private function parsePHRASEVALUE() {
        return $this->parseFIELD();
    }
}

// Example usage:
$input = "AND(field1,value1) OR(field2,value2) NOT(field3,value3)";
$parser = new Parser($input);
$result = $parser->parse();

print_r($result);
*/
/******************************
 * 4th stab at Recursive parser to produce ES DSL query. This handles  AND, OR, NOT, WIldcards and Phrases, from a natural language query,
 *examples of query string inpit
    field1 and value1
    field2 or value2
    not field3 value3
    field4 contains value4*
    "quick brown fox" in field5
* Heres's the grammar
    QUERY      -> AND | OR | NOT | WILDCARD | PHRASE
    AND        -> FIELD "and" VALUE
    OR         -> FIELD "or" VALUE
    NOT        -> "not" FIELD VALUE
    WILDCARD   -> FIELD "contains" VALUE
    PHRASE     -> PHRASEVALUE "in" FIELD
    FIELD      -> string (without spaces)
    VALUE      -> string (without spaces)
    PHRASEVALUE -> string inside double quotes
 ******************************/
/* class Parser {
    private $tokens;
    private $index;

    public function __construct($input) {
        // Split by spaces but keep phrases inside quotes intact
        preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $input, $matches);
        $this->tokens = $matches[0];
        $this->index = 0;
    }

    public function parse() {
        $result = [];
        while ($this->index < count($this->tokens)) {
            if ($this->lookahead() === 'and') {
                $result[] = $this->parseAND();
            } elseif ($this->lookahead() === 'or') {
                $result[] = $this->parseOR();
            } elseif ($this->lookahead() === 'not') {
                $result[] = $this->parseNOT();
            } elseif ($this->lookahead() === 'contains') {
                $result[] = $this->parseWILDCARD();
            } elseif ($this->lookahead(1) === 'in') {
                $result[] = $this->parsePHRASE();
            } else {
                throw new Exception("Unexpected token: " . $this->tokens[$this->index]);
            }
        }
        return $result;
    }

    private function lookahead($offset = 0) {
        return $this->tokens[$this->index + $offset] ?? null;
    }

    private function consume() {
        return $this->tokens[$this->index++];
    }

    private function parseAND() {
        $field = $this->consume();
        $this->consume();  // "and"
        $value = $this->consume();
        return ['and' => [$field => $value]];
    }

    private function parseOR() {
        $field = $this->consume();
        $this->consume();  // "or"
        $value = $this->consume();
        return ['or' => [$field => $value]];
    }

    private function parseNOT() {
        $this->consume();  // "not"
        $field = $this->consume();
        $value = $this->consume();
        return ['not' => [$field => $value]];
    }

    private function parseWILDCARD() {
        $field = $this->consume();
        $this->consume();  // "contains"
        $value = $this->consume();
        return ['wildcard' => [$field => $value]];
    }

    private function parsePHRASE() {
        $phraseValue = trim($this->consume(), '"');  // remove quotes
        $this->consume();  // "in"
        $field = $this->consume();
        return ['phrase' => [$field => $phraseValue]];
    }
}

// Example usage:
$input = 'field1 and value1 field2 or value2 not field3 value3 field4 contains value4* "quick brown fox" in field5';
$parser = new Parser($input);
$result = $parser->parse();

print_r($result);
*/
/*************************
 * This recursive parser for a query string with AND, OR, NOT, wildcards, and phrases outpust Elasticsearch DSL that matches the input query. 
 * This DSL can then be passed to Elasticsearch to perform the search on the "content" field of documents, which is assumed to contain the PDF text.
*  The grammar used is 
    QUERY      -> EXPR
    EXPR       -> TERM AND EXPR | TERM OR EXPR | TERM
    TERM       -> FACTOR NOT TERM | FACTOR
    FACTOR     -> ( EXPR ) | WILDCARD | PHRASE | WORD
    WILDCARD   -> word*
    PHRASE     -> "words in quotes"
    WORD       -> alphanumeric word
* DSL query terms are explained here: https://opendistro.github.io/for-elasticsearch-docs/docs/elasticsearch/bool/#:~:text=The%20bool%20query%20lets%20you,chaining%20together%20several%20simple%20ones.
************************/
class Parser {
    private $tokens;
    private $index;

    public function __construct($input) {
        $this->tokens = preg_split("/\s+|\b/", $input);
        $this->index = 0;
    }

    public function parse() {
        $result = $this->parseEXPR();
        return $result;
    }

    private function parseEXPR() {
        $left = $this->parseTERM();

        if ($this->lookahead() === 'and') {
            $this->consume();
            $right = $this->parseEXPR();
            return [
                'bool' => [
                    'must' => [$left, $right]
                ]
            ];
        } elseif ($this->lookahead() === 'or') {
            $this->consume();
            $right = $this->parseEXPR();
            return [
                'bool' => [
                    'should' => [$left, $right]
                ]
            ];
        }

        return $left;
    }

    private function parseTERM() {
        $left = $this->parseFACTOR();

        if ($this->lookahead() === 'not') {
            $this->consume();
            $right = $this->parseTERM();
            return [
                'bool' => [
                    'must_not' => [$right],
                    'filter' => [$left]
                ]
            ];
        }

        return $left;
    }

    private function parseFACTOR() {
        if ($this->lookahead() === '(') {
            $this->consume();
            $result = $this->parseEXPR();
            if ($this->consume() !== ')') {
                throw new Exception("Expected )");
            }
            return $result;
        } elseif (substr($this->lookahead(), -1) === '*') {
            $word = $this->consume();
            return [
                'wildcard' => [
                    'content' => $word
                ]
            ];
        } elseif ($this->lookahead()[0] === '"') {
            $phrase = $this->consume();
            return [
                'match_phrase' => [
                    'content' => trim($phrase, '"')
                ]
            ];
        } else {
            $word = $this->consume();
            return [
                'match' => [
                    'content' => $word
                ]
            ];
        }
    }

    private function lookahead() {
        if ($this->index < count($this->tokens)) {
            return strtolower($this->tokens[$this->index]);
        }
        return null;
    }

    private function consume() {
        return $this->tokens[$this->index++];
    }
}
// Test the function above
/*
$input = 'FRED and (BILL or BEN) not BRIAN* and "FLOWER MEADOW"';
$parser = new Parser($input);
$result = $parser->parse();

echo json_encode($result, JSON_PRETTY_PRINT);
*/

