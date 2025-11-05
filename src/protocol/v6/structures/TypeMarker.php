<?php

namespace Bolt\protocol\v6\structures;

/**
 * Type markers for vector data
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-vector
 * @package Bolt\protocol\v6\structures
 */
enum TypeMarker: int
{
    case INT_8 = 0xC8;
    case INT_16 = 0xC9;
    case INT_32 = 0xCA;
    case INT_64 = 0xCB;
    case FLOAT_32 = 0xC6;
    case FLOAT_64 = 0xC1;
}
