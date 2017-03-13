<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * proVatar - offline infotar like avatar generator.
 *
 * Long description for file (if any)...
 *
 * PHP version 5/7
 *
 * @package        provatar
 * @author         Progeja <progeja@gmail.com>
 * @copyright  (c) 2017 Progeja
 * @version        GIT: $Id$ - 9.03.2017 9:44 - proVatar.php - provatar
 * @since          0.0.1
 */

namespace provatar;

define('PROVATAR_IMAGES', __DIR__ . '/../www/');

class proVatar
{
    private $options     = []; // seaded
    private $blockSize   = 120; // virtual block size
    private $transparent = false; // läbipaistev?
    private $blocks      = 1; // blokkide arv külje kohta
    private $lengthOf    = []; // mõõdud
    private $quarter;
    private $half;
    private $diagonal;
    private $halfdiag;
    private $shapes;
    private $rotatable;
    private $square;
    private $im;
    private $colors;
    private $size;
    private $centers;
    private $shapes_mat;
    // private $symmetric_num;
    private $rot_mat;
    private $invert_mat;
    private $rotations;

    /**
     * Color settings
     */
    const MONOCHROME = 0;
    const BLACKWHITE = 0;
    const SAMECOLOR  = 1;
    const MULTICOLOR = 2;

    private $proBlocks        = 1;  // number of blocks per side (1..N)
    private $proQuarterHeight = 0;  // rotatable subblock height
    private $proQuarterWidth  = 0;  // rotatable subblock width
    private $proQuarter       = []; // all quarter subblock parameters
    private $proShapes        = []; // List of all possible shapes
    private $proColorMode     = 0;  // Color mode used for avatar generation
    private $proShapesList    = []; // kujundite nimekiri
    private $proShapesInUse   = []; // List of shapes used in avatar generation


    const HALF     = 0;
    const FULL     = 1;
    const SIDE     = 0;
    const DIAGONAL = 1;

    /**
     * @todo:
     */

    /**
     * Demo function
     * Displays all shape variants.
     *
     * @return string
     */
    public function display_parts_demo()
    {
        self::__construct(1);
        $output = '';
        for ($i = 0; $i < count($this->shapes); $i++) {
            $this->shapes_mat = [$i];
            $this->invert_mat = [1];
            $output .=
                '<div style="width:70px;height:70px;display:inline-block;"><div style="color:maroon;float:left;">' . substr("0000{$i}.",
                    -3) . '</div>' .
                $this->build(
                    $seed = 'example' . $i,
                    $altImgText = '',
                    $img = true,
                    $outsize = 32,
                    $write = true,
                    $random = false) . "</div>&nbsp;&nbsp;";
            if (($i + 1) % 10 == 0) {
                $output .= "<br/>\n";
            }
        }
        self::__construct(1);

        return $output;
    }

    /**
     * proVatar constructor.
     *
     * @param int $blocks Number of shape blocks per side
     */
    public function __construct($blocks = 1)
    {
        if ($blocks) {
            $this->blocks = $blocks;
        }

        $this->init();
    }

    private function init()
    {
        $this->dimensions();
        $this->defineShapes();
    }

    private function dimensions()
    {
        $this->size = $this->blocks * $this->blockSize;
        $this->lengthOf = [
            [0.25 * $this->blockSize, sqrt(0.125) * $this->blockSize], // half size of line, diagonal
            [0.5 * $this->blockSize, sqrt(0.5) * $this->blockSize], // full size of line, diagonal
        ];

        $this->proQuarterHeight = (int)floor($this->proBlocks / 2);
        $this->proQuarterWidth = $this->proQuarterHeight - $this->proQuarterHeight;

    }

    private function defineShapes()
    {
        // Shape points in polar cordinates
        // first param is angle from 0 (rad), second is relative distance from center-point
        //0. full block
        $this->proShapesList[] = [[[0.25, 1], [0.75, 1], [1.25, 1], [1.75, 1]]];
        //1 rectangular half block
        $this->proShapesList[] = [[[0.5, 1], [0.75, 1], [1.25, 1], [1.5, 1]]];
        //2 diagonal half block
        $this->proShapesList[] = [[[0.25, 1], [0.75, 1], [1.25, 1]]];
        //3 triangle
        $this->proShapesList[] = [[[0.5, 1], [1.25, 1], [1.75, 1]]];
        //4 diamond
        $this->proShapesList[] = [[[0, 1], [0.5, 1], [1, 1], [1.5, 1]]];
        //5 stretched diamond
        $this->proShapesList[] = [[[0, 1], [0.75, 1], [1.5, 1], [1.75, 1]]];
        // 6 triple triangle
        $this->proShapesList[] = [
            [[0, 0.5], [0.5, 1], [1, 0.5]], // 1. triangle
            [[0, 0.5], [1.75, 1], [1.5, 1]], // 2. triangle
            [[1.5, 1], [1, 0.5], [1.25, 1]], // 3. triangle
        ];
        //7 pointer
        $this->proShapesList[] = [[[0, 1], [0.75, 1], [1.5, 1]]];
        //9 center square
        $this->proShapesList[] = [[[0.25, 0.5], [0.75, 0.5], [1.25, 0.5], [1.75, 0.5]]];
        //9 double triangle diagonal
        $this->proShapesList[] = [[[1, 1], [1.25, 1], [0, 0]], [[0.25, 1], [0.5, 1], [0, 0]]];
        //10 diagonal square
        $this->proShapesList[] = [[[0.5, 1], [0.75, 1], [1, 1], [0, 0]]];
        //11 quarter triangle out
        $this->proShapesList[] = [[[0, 1], [1, 1], [1.5, 1]]];
        //12quarter triangle in
        $this->proShapesList[] = [[[1.75, 1], [1.25, 1], [0, 0]]];
        //13 eighth triangle in
        $this->proShapesList[] = [[[0.5, 1], [1, 1], [0, 0]]];
        //14 eighth triangle out
        $this->proShapesList[] = [[[0.5, 1], [0.75, 1], [1, 1]]];
        //15 double corner square
        $this->proShapesList[] = [
            [[0.5, 1], [0.75, 1], [1, 1], [0, 0]],
            [[0, 1], [1.75, 1], [1.5, 1], [0, 0]],
        ];
        //16 double quarter triangle in
        $this->proShapesList[] = [
            [[1.75, 1], [1.25, 1], [0, 0]],
            [[0.25, 1], [0.75, 1], [0, 0]],
        ];
        //17 tall quarter triangle
        $this->proShapesList[] = [[[0.5, 1], [0.75, 1], [1.25, 1]]];
        //18 double tall quarter triangle
        $this->proShapesList[] = [
            [[0.5, 1], [0.75, 1], [1.25, 1]],
            [[0.25, 1], [0.5, 1], [1.5, 1]],
        ];
        //19 tall quarter + eighth triangles
        $this->proShapesList[] = [
            [[0.5, 1], [0.75, 1], [1.25, 1]],
            [[0.25, 1], [0.5, 1], [0, 0]],
        ];
        //20 tipped over tall triangle
        $this->proShapesList[] = [[[0.75, 1], [1.5, 1], [1.75, 1]]];
        //21 triple triangle diagonal
        $this->proShapesList[] = [
            [[1, 1], [1.25, 1], [0, 0]],
            [[0.25, 1], [0.5, 1], [0, 0]],
            [[0, 1], [0, 0], [1.5, 1]],
        ];
        //22 double triangle flat
        $this->proShapesList[] = [
            [[0, 0.5], [1.75, 1], [1.5, 1]],
            [[1.5, 1], [1, 0.5], [1.25, 1]],
        ];
        //23 opposite 8th triangles
        $this->proShapesList[] = [
            [[0, 0.5], [0.25, 1], [1.75, 1]],
            [[1, 0.5], [0.75, 1], [1.25, 1]],
        ];
        //24 opposite 8th triangles + diamond
        $this->proShapesList[] = [
            [[0, 0.5], [0.25, 1], [1.75, 1]],
            [[1, 0.5], [0.75, 1], [1.25, 1]],
            [[1, 0.5], [0.5, 1], [0, 0.5], [1.5, 1]],
        ];
        //25 small diamond
        $this->proShapesList[] = [[[0, 0.5], [0.5, 0.5], [1, 0.5], [1.5, 0.5]]];
        //26 4 opposite 8th triangles
        $this->proShapesList[] = [
            [[0, 0.5], [0.25, 1], [1.75, 1]],
            [[1, 0.5], [0.75, 1], [1.25, 1]],
            [[1.5, 0.5], [1.25, 1], [1.75, 1]],
            [[0.5, 0.5], [0.75, 1], [0.25, 1]],
        ];
        //27 double quarter triangle parallel
        $this->proShapesList[] = [
            [[1.75, 1], [1.25, 1], [0, 0]],
            [[0, 1], [0.5, 1], [1, 1]],
        ];
        //28 double overlapping tipped over tall triangle
        $this->proShapesList[] = [
            [[0.75, 1], [1.5, 1], [1.75, 1]],
            [[1.25, 1], [0.5, 1], [0.25, 1]],
        ];
        //29 opposite double tall quarter triangle
        $this->proShapesList[] = [
            [[0.5, 1], [0.75, 1], [1.25, 1]],
            [[1.75, 1], [0.25, 1], [1.5, 1]],
        ];
        //30 4 opposite 8th triangles+tiny diamond
        $this->proShapesList[] = [
            [[0, 0.5], [0.25, 1], [1.75, 1]],
            [[1, 0.5], [0.75, 1], [1.25, 1]],
            [[1.5, 0.5], [1.25, 1], [1.75, 1]],
            [[0.5, 0.5], [0.75, 1], [0.25, 1]],
            [[0, 0.5], [0.5, 0.5], [1, 0.5], [1.5, 0.5]],
        ];
        //31 diamond C
        $this->proShapesList[] = [
            [
                [0, 1],
                [0.5, 1],
                [1, 1],
                [1.5, 1],
                [1.5, 0.5],
                [1, 0.5],
                [0.5, 0.5],
                [0, 0.5],
            ],
        ];
        //32 narrow diamond
        $this->proShapesList[] = [[[0, 0.5], [0.5, 1], [1, 0.5], [1.5, 1]]];
        //33 quadruple triangle diagonal
        $this->proShapesList[] = [
            [[1, 1], [1.25, 1], [0, 0]],
            [[0.25, 1], [0.5, 1], [0, 0]],
            [[0, 1], [0, 0], [1.5, 1]],
            [[0.5, 1], [0.75, 1], [1, 1]],
        ];
        //34 diamond donut
        $this->proShapesList[] = [
            [
                [0, 1],
                [0.5, 1],
                [1, 1],
                [1.5, 1],
                [0, 1],
                [0, 0.5],
                [1.5, 0.5],
                [1, 0.5],
                [0.5, 0.5],
                [0, 0.5],
            ],
        ];
        //35 triple turning triangle
        $this->proShapesList[] = [
            [[0.5, 1], [0.25, 1], [0, 0.5]],
            [[0, 1], [1.75, 1], [1.5, 0.5]],
            [[1.5, 1], [1.25, 1], [1, 0.5]],
        ];
        //36 double turning triangle
        $this->proShapesList[] = [
            [[0.5, 1], [0.25, 1], [0, 0.5]],
            [[0, 1], [1.75, 1], [1.5, 0.5]],
        ];
        //37 diagonal opposite inward double triangle
        $this->proShapesList[] = [
            [[0.5, 1], [0.25, 1], [0, 0.5]],
            [[1.5, 1], [1.25, 1], [1, 0.5]],
        ];
        //38 star fleet
        $this->proShapesList[] = [[[0.5, 1], [1.25, 1], [0, 0], [1.75, 1]]];
        //39 hollow half triangle
        $this->proShapesList[] = [
            [
                [0.5, 1],
                [1.25, 1],
                [0, 0],
                [1.75, 0.5],
                [1.25, 0.5],
                [1.25, 1],
                [1.75, 1],
            ],
        ];
        //40 double eighth triangle out
        $this->proShapesList[] = [
            [[0.5, 1], [0.75, 1], [1, 1]],
            [[1.5, 1], [1.75, 1], [0, 1]],
        ];
        //41 double slanted square
        $this->proShapesList[] = [
            [[0.5, 1], [0.75, 1], [1, 1], [1, 0.5]],
            [[1.5, 1], [1.75, 1], [0, 1], [0, 0.5]],
        ];
        //42 double diamond
        $this->proShapesList[] = [
            [[0, 1], [0.25, 0.5], [0, 0], [1.75, 0.5]],
            [[1, 1], [0.75, 0.5], [0, 0], [1.25, 0.5]],
        ];
        //43 double pointer
        $this->proShapesList[] = [
            [[0, 1], [0.25, 1], [0, 0], [1.75, 0.5]],
            [[1, 1], [0.75, 0.5], [0, 0], [1.25, 1]],
        ];

        $this->rotatable = [1, 4, 8, 25, 26, 30, 34]; // kujundid, mida pole mõtet pöörata

        $this->square = $this->proShapesList[0][0];


    }

    /**
     * Define all used shapes.
     */
    private function shapes()
    {
        // Shape descriptions based on area center-point.
        // First parameter is angle from 0 and second is distants from center
        // 0-angle pointed to down (SOUTH) [0-SOUTH/90-WEST/180-NORTH/270-EAST]
        $this->shapes = [
            [[[90, $this->half], [135, $this->diagonal], [225, $this->diagonal], [270, $this->half]]],
            //0 rectangular half block
            [[[45, $this->diagonal], [135, $this->diagonal], [225, $this->diagonal], [315, $this->diagonal]]],
            //1 full block
            [[[45, $this->diagonal], [135, $this->diagonal], [225, $this->diagonal]]],
            //2 diagonal half block
            [[[90, $this->half], [225, $this->diagonal], [315, $this->diagonal]]],
            //3 triangle
            [[[0, $this->half], [90, $this->half], [180, $this->half], [270, $this->half]]],
            //4 diamond
            [[[0, $this->half], [135, $this->diagonal], [270, $this->half], [315, $this->diagonal]]],
            //5 stretched diamond
            [
                [[0, $this->quarter], [90, $this->half], [180, $this->quarter]],
                [[0, $this->quarter], [315, $this->diagonal], [270, $this->half]],
                [[270, $this->half], [180, $this->quarter], [225, $this->diagonal]],
            ],
            // 6 triple triangle
            [[[0, $this->half], [135, $this->diagonal], [270, $this->half]]],
            //7 pointer
            [[[45, $this->halfdiag], [135, $this->halfdiag], [225, $this->halfdiag], [315, $this->halfdiag]]],
            //9 center square
            [[[180, $this->half], [225, $this->diagonal], [0, 0]], [[45, $this->diagonal], [90, $this->half], [0, 0]]],
            //9 double triangle diagonal
            [[[90, $this->half], [135, $this->diagonal], [180, $this->half], [0, 0]]],
            //10 diagonal square
            [[[0, $this->half], [180, $this->half], [270, $this->half]]],
            //11 quarter triangle out
            [[[315, $this->diagonal], [225, $this->diagonal], [0, 0]]],
            //12quarter triangle in
            [[[90, $this->half], [180, $this->half], [0, 0]]],
            //13 eighth triangle in
            [[[90, $this->half], [135, $this->diagonal], [180, $this->half]]],
            //14 eighth triangle out
            [
                [[90, $this->half], [135, $this->diagonal], [180, $this->half], [0, 0]],
                [[0, $this->half], [315, $this->diagonal], [270, $this->half], [0, 0]],
            ],
            //15 double corner square
            [
                [[315, $this->diagonal], [225, $this->diagonal], [0, 0]],
                [[45, $this->diagonal], [135, $this->diagonal], [0, 0]],
            ],
            //16 double quarter triangle in
            [[[90, $this->half], [135, $this->diagonal], [225, $this->diagonal]]],
            //17 tall quarter triangle
            [
                [[90, $this->half], [135, $this->diagonal], [225, $this->diagonal]],
                [[45, $this->diagonal], [90, $this->half], [270, $this->half]],
            ],
            //18 double tall quarter triangle
            [
                [[90, $this->half], [135, $this->diagonal], [225, $this->diagonal]],
                [[45, $this->diagonal], [90, $this->half], [0, 0]],
            ],
            //19 tall quarter + eighth triangles
            [[[135, $this->diagonal], [270, $this->half], [315, $this->diagonal]]],
            //20 tipped over tall triangle
            [
                [[180, $this->half], [225, $this->diagonal], [0, 0]],
                [[45, $this->diagonal], [90, $this->half], [0, 0]],
                [[0, $this->half], [0, 0], [270, $this->half]],
            ],
            //21 triple triangle diagonal
            [
                [[0, $this->quarter], [315, $this->diagonal], [270, $this->half]],
                [[270, $this->half], [180, $this->quarter], [225, $this->diagonal]],
            ],
            //22 double triangle flat
            [
                [[0, $this->quarter], [45, $this->diagonal], [315, $this->diagonal]],
                [[180, $this->quarter], [135, $this->diagonal], [225, $this->diagonal]],
            ],
            //23 opposite 8th triangles
            [
                [[0, $this->quarter], [45, $this->diagonal], [315, $this->diagonal]],
                [[180, $this->quarter], [135, $this->diagonal], [225, $this->diagonal]],
                [[180, $this->quarter], [90, $this->half], [0, $this->quarter], [270, $this->half]],
            ],
            //24 opposite 8th triangles + diamond
            [[[0, $this->quarter], [90, $this->quarter], [180, $this->quarter], [270, $this->quarter]]],
            //25 small diamond
            [
                [[0, $this->quarter], [45, $this->diagonal], [315, $this->diagonal]],
                [[180, $this->quarter], [135, $this->diagonal], [225, $this->diagonal]],
                [[270, $this->quarter], [225, $this->diagonal], [315, $this->diagonal]],
                [[90, $this->quarter], [135, $this->diagonal], [45, $this->diagonal]],
            ],
            //26 4 opposite 8th triangles
            [
                [[315, $this->diagonal], [225, $this->diagonal], [0, 0]],
                [[0, $this->half], [90, $this->half], [180, $this->half]],
            ],
            //27 double quarter triangle parallel
            [
                [[135, $this->diagonal], [270, $this->half], [315, $this->diagonal]],
                [[225, $this->diagonal], [90, $this->half], [45, $this->diagonal]],
            ],
            //28 double overlapping tipped over tall triangle
            [
                [[90, $this->half], [135, $this->diagonal], [225, $this->diagonal]],
                [[315, $this->diagonal], [45, $this->diagonal], [270, $this->half]],
            ],
            //29 opposite double tall quarter triangle
            [
                [[0, $this->quarter], [45, $this->diagonal], [315, $this->diagonal]],
                [[180, $this->quarter], [135, $this->diagonal], [225, $this->diagonal]],
                [[270, $this->quarter], [225, $this->diagonal], [315, $this->diagonal]],
                [[90, $this->quarter], [135, $this->diagonal], [45, $this->diagonal]],
                [[0, $this->quarter], [90, $this->quarter], [180, $this->quarter], [270, $this->quarter]],
            ],
            //30 4 opposite 8th triangles+tiny diamond
            [
                [
                    [0, $this->half],
                    [90, $this->half],
                    [180, $this->half],
                    [270, $this->half],
                    [270, $this->quarter],
                    [180, $this->quarter],
                    [90, $this->quarter],
                    [0, $this->quarter],
                ],
            ],
            //31 diamond C
            [[[0, $this->quarter], [90, $this->half], [180, $this->quarter], [270, $this->half]]],
            //32 narrow diamond
            [
                [[180, $this->half], [225, $this->diagonal], [0, 0]],
                [[45, $this->diagonal], [90, $this->half], [0, 0]],
                [[0, $this->half], [0, 0], [270, $this->half]],
                [[90, $this->half], [135, $this->diagonal], [180, $this->half]],
            ],
            //33 quadruple triangle diagonal
            [
                [
                    [0, $this->half],
                    [90, $this->half],
                    [180, $this->half],
                    [270, $this->half],
                    [0, $this->half],
                    [0, $this->quarter],
                    [270, $this->quarter],
                    [180, $this->quarter],
                    [90, $this->quarter],
                    [0, $this->quarter],
                ],
            ],
            //34 diamond donut
            [
                [[90, $this->half], [45, $this->diagonal], [0, $this->quarter]],
                [[0, $this->half], [315, $this->diagonal], [270, $this->quarter]],
                [[270, $this->half], [225, $this->diagonal], [180, $this->quarter]],
            ],
            //35 triple turning triangle
            [
                [[90, $this->half], [45, $this->diagonal], [0, $this->quarter]],
                [[0, $this->half], [315, $this->diagonal], [270, $this->quarter]],
            ],
            //36 double turning triangle
            [
                [[90, $this->half], [45, $this->diagonal], [0, $this->quarter]],
                [[270, $this->half], [225, $this->diagonal], [180, $this->quarter]],
            ],
            //37 diagonal opposite inward double triangle
            [[[90, $this->half], [225, $this->diagonal], [0, 0], [315, $this->diagonal]]],
            //38 star fleet
            [
                [
                    [90, $this->half],
                    [225, $this->diagonal],
                    [0, 0],
                    [315, $this->halfdiag],
                    [225, $this->halfdiag],
                    [225, $this->diagonal],
                    [315, $this->diagonal],
                ],
            ],
            //39 hollow half triangle
            [
                [[90, $this->half], [135, $this->diagonal], [180, $this->half]],
                [[270, $this->half], [315, $this->diagonal], [0, $this->half]],
            ],
            //40 double eighth triangle out
            [
                [[90, $this->half], [135, $this->diagonal], [180, $this->half], [180, $this->quarter]],
                [[270, $this->half], [315, $this->diagonal], [0, $this->half], [0, $this->quarter]],
            ],
            //42 double slanted square
            [
                [[0, $this->half], [45, $this->halfdiag], [0, 0], [315, $this->halfdiag]],
                [[180, $this->half], [135, $this->halfdiag], [0, 0], [225, $this->halfdiag]],
            ],
            //43 double diamond
            [
                [[0, $this->half], [45, $this->diagonal], [0, 0], [315, $this->halfdiag]],
                [[180, $this->half], [135, $this->halfdiag], [0, 0], [225, $this->diagonal]],
            ],
            //44 double pointer
        ];

        $this->rotatable = [1, 4, 8, 25, 26, 30, 34]; // kujundid, mida pole mõtet pöörata

        $this->square = $this->shapes[1][0];


    }

    /**
     * @param integer $x
     * @param integer $y
     *
     * @return array|float|int
     */
    private function xy2symmetric($x, $y)
    {
        $index = [
            floor(abs(($this->blocks - 1) / 2 - $x)),
            floor(abs(($this->blocks - 1) / 2 - $y)),
        ];
        sort($index);
        $index[1] *= ceil($this->blocks / 2);
        $index = array_sum($index);

        return $index;
    }

    function build(
        $seed = '',
        $altImgText = '',
        $img = true,
        $outsize = '',
        $write = true,
        $random = true,
        $displaysize = ''
    ) {
        //make an identicon and return the filepath or if write=false return picture directly
        if (function_exists("gd_info")) {
            // init random seed
            if ($random) {
                $id = substr(sha1($seed), 0, 10);
            } else {
                $id = $seed;
            }
            if ($outsize == '') {
                $outsize = $this->options['size'];
            }
            if ($displaysize == '') {
                $displaysize = $outsize;
            }

            $filename = substr(sha1($id . substr('temp@mail.ee', 0, 5)), 0, 15) . '_' . intval($displaysize) . '.png';

            if (!file_exists(PROVATAR_IMAGES . $filename)) {
                // Loome pildi põhja
                $this->im = imagecreatetruecolor($this->size, $this->size);
                $this->colors = [imagecolorallocate($this->im, 255, 255, 255)]; // white
                if ($random) { // kas valida suvalised värvid?
                    $this->set_randomness($id);
                } else {
                    $this->colors = [
                        imagecolorallocate($this->im, 255, 255, 255), // white
                        imagecolorallocate($this->im, 0, 0, 0), // black
                    ];
                    $this->transparent = false;
                };
                imagefill($this->im, 0, 0, $this->colors[0]); // täidame tausta valgega
                for ($i = 0; $i < $this->blocks; $i++) {
                    for ($j = 0; $j < $this->blocks; $j++) {
                        $this->draw_shape($i, $j);
                    }
                }

                $out = imagecreatetruecolor($outsize, $outsize);
                imagesavealpha($out, true);
                imagealphablending($out, false);
                imagecopyresampled($out, $this->im, 0, 0, 0, 0, $outsize, $outsize, $this->size, $this->size);
                imagedestroy($this->im);
                if ($write) {
                    $wrote = imagepng($out, PROVATAR_IMAGES . $filename);
                    if (!$wrote) {
                        return false;
                    } //something went wrong but don't want to mess up blog layout
                } else {
                    header("Content-type: image/png");
                    imagepng($out);
                }
                imagedestroy($out);
            }
            $filename = 'http://localhost:8888/' . $filename;
            if ($img) {
                $filename = '<img class="identicon" src="' . $filename . '" alt="' . str_replace('"', "'",
                        $altImgText) . ' Identicon" height="' . $displaysize . '" width="' . $displaysize . '" style="margin:3px;padding:1px;border:1px solid gray;" />';
            }

            return $filename;
        } else { //php GD image manipulation is required
            throw new \Exception('GD library not installed!');
            //return false; //php GD image isn't installed but don't want to mess up blog layout
        }
    }


    /**
     * Generates random colors for proVatar
     *
     * @param string $seed
     */
    private function set_randomness($seed = '')
    {
        //set seed
        mt_srand(hexdec($seed));
        foreach ($this->rot_mat as $key => $value) {
            $this->rot_mat[$key] = mt_rand(0, 3) * 90;
            $this->invert_mat[$key] = mt_rand(0, 1);
            #&$this->blocks%2
            if ($key == 0) {
                $this->shapes_mat[$key] = $this->rotatable[mt_rand(0, count($this->rotatable) - 1)];
            } else {
                $this->shapes_mat[$key] = mt_rand(0, count($this->shapes) - 1);
            }
        }
        $backcolors = [255, 255, 255];
        $forecolors = [
            mt_rand($this->options['forer'][0], $this->options['forer'][1]),
            mt_rand($this->options['foreg'][0], $this->options['foreg'][1]),
            mt_rand($this->options['foreb'][0], $this->options['foreb'][1]),
        ];
        $this->colors[1] = imagecolorallocate($this->im, $forecolors[0], $forecolors[1], $forecolors[2]);

        if (array_sum($this->options['backr']) + array_sum($this->options['backg']) + array_sum($this->options['backb']) == 0) {
            $this->colors[0] = imagecolorallocatealpha($this->im, 0, 0, 0, 127);
            $this->transparent = true;
            imagealphablending($this->im, false);
            imagesavealpha($this->im, true);
        } else {
            $backcolors = [
                mt_rand($this->options['backr'][0], $this->options['backr'][1]),
                mt_rand($this->options['backg'][0], $this->options['backg'][1]),
                mt_rand($this->options['backb'][0], $this->options['backb'][1]),
            ];
            $this->colors[0] = imagecolorallocate($this->im, $backcolors[0], $backcolors[1], $backcolors[2]);
        }
        if ($this->options['grey']) {
            $this->colors[1] = imagecolorallocate($this->im, $forecolors[0], $forecolors[0], $forecolors[0]);
            if (!$this->transparent) {
                $this->colors[0] = imagecolorallocate($this->im, $backcolors[0], $backcolors[0], $backcolors[0]);
            }
        }
    }

    private function draw_shape($x, $y)
    {
        $index = $this->xy2symmetric($x, $y);
        $shape = $this->shapes[$this->shapes_mat[$index]];
        $invert = $this->invert_mat[$index];
        $rotation = $this->rot_mat[$index];
        $centers = $this->centers[$x][$y];
        $invert2 = abs($invert - 1);
        $points = $this->calc_x_y($this->square, $centers, 0);
        $num = count($points) / 2;
        imagefilledpolygon($this->im, $points, $num, $this->colors[$invert2]);
        foreach ($shape as $subshape) {
            $points = $this->calc_x_y($subshape, $centers, $rotation + $this->rotations[$x][$y]);
            $num = count($points) / 2;
            imagefilledpolygon($this->im, $points, $num, $this->colors[$invert]);
        }
    }

    /**
     * convert array(array(heading1,distance1),array(heading1,distance1)) to array(x1,y1,x2,y2)
     *
     * @param     $array
     * @param     $centers
     * @param int $rotation
     *
     * @return array
     */
    private function calc_x_y($array, $centers, $rotation = 0)
    {
        $output = [];
        $centerx = $centers[0];
        $centery = $centers[1];
        while ($thispoint = array_pop($array)) {
            $y = round($centery + sin(deg2rad($thispoint[0] + $rotation)) * $thispoint[1]);
            $x = round($centerx + cos(deg2rad($thispoint[0] + $rotation)) * $thispoint[1]);
            array_push($output, $x, $y);
        }

        return $output;
    }

}

