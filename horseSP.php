<?php
ini_set("display_errors", true);
error_reporting(E_ALL);

const ROWS = 8;
const COLUMNS = 8;

class Square
{
    /**
     * square label e.g. "a1", "c5" etc.
     * @var string
     */
    private $label;

    /**
     * index in array which internally represents board
     * @var int
     */
    private $boardIndex;

    /**
     * index of row in real board number in <1..8>
     * @var int
     */
    private $rowIndex;

    /**
     * index of column in real board number in <1..8>
     * where 1 corresponds "a"
     * where 2 corresponds "b"
     * ...
     * @var int
     */
    private $columnIndex;

    /**
     * Square from which it was jumped into current square
     * It's used for path building
     * @var Square
     */
    public $from;

    /**
     * @param string $label
     * @param int $boardIndex
     * @param int $rowIndex
     * @param int $columnIndex
     */
    public function __construct($label, $boardIndex, $rowIndex, $columnIndex)
    {
        $this->label = $label;
        $this->boardIndex = $boardIndex;
        $this->rowIndex = $rowIndex;
        $this->columnIndex = $columnIndex;
    }

    public function getLabel()
    {
        return $this->label;
    }

    /**
     * gets list of indexes from board for all
     * possible jumps from current square
     * @return int[]
     */
    public function getPossibleJumpsIndexes()
    {
        $columnIndex = $this->columnIndex;
        $rowIndex = $this->rowIndex;
        $currentIndex = $this->boardIndex;

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

    public function getPossibleJumpsIndexesTricky()
    {
        foreach (range(0, 7) as $index) $positions[$index] = new stdClass();

        // generates all possible jumps
        $counter = 0;
        foreach ([["columnIndex", "rowIndex"], ["rowIndex", "columnIndex"]] as $indexes) {
            foreach ([[2, 1], [2, -1], [-2, 1], [-2, -1]] as $jumpValues) {
                $positions[$counter]->{$indexes[0]} = $this->{$indexes[0]} + $jumpValues[0];
                $positions[$counter++]->{$indexes[1]} = $this->{$indexes[1]} + $jumpValues[1];
            }
        }

        // filter jumps outside the chess board, calculates board offset for existing one
        foreach ($positions as $position) {
            if ($position->columnIndex < 1 || $position->columnIndex > COLUMNS
                || $position->rowIndex < 1 || $position->rowIndex > ROWS) {
                continue;
            }
            $jumps[] = (ROWS - $position->rowIndex) * COLUMNS + $position->columnIndex - 1;
        }
        return $jumps;
    }
}

class PathFinder
{
    /**
     * @var Square[]
     */
    private $board;

    /**
     * @var Square
     */
    private $start;

    /**
     * @var Square
     */
    private $end;

    /**
     * @param string $startLabel e.g. "a8"
     * @param string $endLabel e.g "h1"
     */
    public function __construct($startLabel, $endLabel)
    {
        // initialize board ROWS x COLUMNS, as indexed array of Square objects
        // check the start, end squares
        $counter = 0;
        for ($row = ROWS; $row > 0; $row--) {
            for ($column = 1; $column <= COLUMNS; $column++) {
                $label = chr(ord("a") + $column - 1) . $row;
                $square = new Square($label, $counter, $row, $column);
                if ($label == $startLabel) {
                    $start = $square;
                } elseif ($label == $endLabel) {
                    $end = $square;
                }
                $board[$counter++] = $square;
            }
        }

        $this->board = $board;
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Gets path from start to the end
     * @return Square[]
     */
    public function getPath()
    {
        // jump until the end is reached
        $positions = [$this->start];
        while (!$this->end->from) {
            $positions = $this->getJumps($positions);
        }

        // list of jumps is in reverse order, from end to start
        $square = $this->end;
        while ($square !== $this->start) {
            $path[] = $square;
            $square = $square->from;
        }
        $path[] = $this->start;
        return array_reverse($path);
    }

    /**
     * Gets list of positions for jumps from particular list of positions
     * If the end square is reached, null is returned
     * @param Square[]
     * @return Square[]|null
     */
    private function getJumps(array $positions)
    {
        foreach ($positions as $position) {
            foreach ($position->getPossibleJumpsIndexesTricky() as $boardIndex) {
                /** @var Square */
                $jumpedSquare = $this->board[$boardIndex];
                if ($jumpedSquare->from || $jumpedSquare === $this->start) {
                    continue;
                }
                $jumpedSquare->from = $position;
                if ($jumpedSquare === $this->end) {
                    return;
                }
                $createdPositions[] = $jumpedSquare;
            }
        }

        if (!isset($createdPositions)) {
            throw new RuntimeException("no newly created positions presented!");
        }
        return $createdPositions;
    }
}

// ========================== main program entry point ==========================
$finder = new PathFinder("a8", "h2");
$squarePath = $finder->getPath();

// print the path as string
$path = "";
foreach ($squarePath as $square) {
    if ($path) $path .= ", ";
    $path .= $square->getLabel();
}
echo $path . "\n";

// testing results
// a8, c7, e8, f6, g4, h2
// c5, e4, d6, c8
// b1, d2, f3, h4, g6, f8
