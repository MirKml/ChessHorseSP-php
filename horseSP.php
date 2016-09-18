<?php
ini_set("display_errors", true);
error_reporting(E_ALL);

$startLabel = "a8";
$endLabel = "h2";

const ROWS = 8;
const COLUMNS = 8;

class Square
{
    /**
     * square label e.g. "a1", "c5" etc.
     * @var string
     */
    private $label = "";
    
    /**
     * is this one start position?
     * @var boo
     */
    public $isStart = false;

    /**
     * is this one end position?
     * @var boo
     */
    public $isEnd = false;

    /**
     * Square from which it was jumped into current square
     * It's used for path building
     * @var Square
     */ 
    public $from;

    /**
     * @param string $label
     */
    public function __construct($label)
    {
        $label = strtolower($label);
        if (!strlen($label) === 2) {
            throw new \InvalidArgumentException("label '$label' isn't valid label");
        }
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    /**
     * gets index of square in board
     * @return int
     */
    public function getBoardIndex()
    {
        $columnOffset = ord($this->label[0]) - ord("a");
        return ((ROWS - $this->label[1]) * COLUMNS) + $columnOffset;
    }
    
    /**
     * creates square by board index (0, 1, 2 ..), 
     * initilizes correspondend label: ("a8", "b8")
     * @return Square
     */
    public static function createByIndex($index)
    {
        $index++;
        $row = (int)($index / ROWS);
        $remainder = $index - $row * COLUMNS;
        if ($remainder > 0) {
            $row = ROWS - $row;
            $column = chr(ord("a") + ($remainder - 1));
        } else {
            $row = ROWS - $row + 1;
            // latest column
            $column = chr(ord("a") + COLUMNS - 1);
        }

        $square = new self($column . $row);
        return $square;
    }

    /**
     * gets index of current square row column from range 1 .. ROWS
     * @return int
     */
    private function getRowIndex()
    {
        return $this->label[1];
    }

    /**
     * gets index of current square column from range 1 .. COLUMNS
     * for "a" column returns 1, 
     * for "b" column returns 2
     * ....
     * @return int
     */
    private function getColumnIndex()
    {
        return ord($this->label[0]) - ord("a") + 1;
    }

    /**
     * gets list of indexes from board for all
     * possible jumps from current square
     * @return int[]
     */
    public function getPossibleJumpsIndexes()
    {
        $columnIndex = $this->getColumnIndex();
        $rowIndex = $this->getRowIndex();
        $currentIndex = $this->getBoardIndex(); 

        // right up jump
        if ($columnIndex < COLUMNS - 1 && $rowIndex < ROWS) {
            $jumps[] = $currentIndex + 2 - COLUMNS;
        }
        // right down jump
        if ($columnIndex < COLUMNS - 1 && $rowIndex > 1) {
            $jumps[] = $currentIndex + 2 + COLUMNS;
        }

        // up right jump
        if ($columnIndex < COLUMNS && $rowIndex < ROWS - 1) {
            $jumps[] = $currentIndex - 2 * COLUMNS + 1;
        }
        // up left jump
        if ($columnIndex > 1 && $rowIndex < ROWS - 1) {
            $jumps[] = $currentIndex - 2 * COLUMNS - 1;
        }

        // left up jump
        if ($columnIndex > 2 && $rowIndex < ROWS) {
            $jumps[] = $currentIndex - 2 - COLUMNS;
        }
        // left down jump
        if ($columnIndex > 2 && $rowIndex > 1) {
            $jumps[] = $currentIndex - 2 + COLUMNS;
        }

        // down left jump
        if ($columnIndex > 1 && $rowIndex > 2) {
            $jumps[] = $currentIndex + 2 * COLUMNS - 1;
        }
        // down right jump
        if ($columnIndex < COLUMNS && $rowIndex > 2) {
            $jumps[] = $currentIndex + 2 * COLUMNS + 1;
        }

        return $jumps;
    }
}

class PathFinder
{
    /**
     * @var array
     */
    private $board;

    /**
     * @var Square
     */
    private $start;

    public function __construct(array $board, Square $start)
    {
        // add start square into board 
        $board[$start->getBoardIndex()] = $start;
        $this->board = $board;
        $this->start = $start;
    }
    
    public function find(Square $end)
    {
        // add end square into board 
        $this->board[$end->getBoardIndex()] = $end;
        $this->makeJumps([$this->start]);
        return $end;
    }

    /**
     * Recursively jumps through all passed positions
     * and its newly created positions, and so on.
     * Ends when the end position is reached.
     * @param Square[] positions
     */
    private function makeJumps(array $positions)
    {
        foreach ($positions as $position) {
            foreach ($position->getPossibleJumpsIndexes() as $boardIndex) {
                // position isn't in board, so create it
                // it's newly created position for testing in next jump
                if (!($square = $this->board[$boardIndex])) {
                    $square = Square::createByIndex($boardIndex);
                    $this->board[$boardIndex] = $square;
                    $createdPositions[] = $square;
                }
                if (!$square->from) {
                    $square->from = $position;
                }
                if ($square->isEnd) {
                    return;
                }
            }
        }

        if (!isset($createdPositions)) {
            throw new Exception("no newly created positions presented!");
        }
        $this->makeJumps($createdPositions);
    }
}

// ========================== main program entry point ==========================

// initialize board ROWS x COLUMNS, as simple indexed array,
// all indexes has "0" value, which means empty square
$board = array_fill(0, ROWS * COLUMNS, 0);

$start = new Square($startLabel);
$start->isStart = true;
$end = new Square($endLabel);
$end->isEnd = true;

$finder = new PathFinder($board, $start);
$end = $finder->find($end);

// list of jumps is in reverse order
// from end to start 
$square = $end;
while (!$square->isStart) {
    $path[] = $square->getLabel();
    $square = $square->from;
}
$path[] = $square->getLabel();

echo implode(", ", array_reverse($path)) . "\n";

// results
// a8, c7, e8, f6, g4, h2
// a8, c7, e8, f6, g4, h2

