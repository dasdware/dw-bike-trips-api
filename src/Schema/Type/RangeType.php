<?php

namespace DW\BikeTrips\API\Schema\Type;

use DateTime;
use DW\BikeTrips\API\Schema\Types;
use DW\BikeTrips\API\Utils\DateTimeRange;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\InputObjectType;


class RangeType extends InputObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Range',
            'description' => 'Time range that limits the entries of the dataset',
            'fields' =>  [
                'from' => ['type' => Types::timestamp()],
                'to' => ['type' => Types::timestamp()],
                'name' => ['type' => Types::rangeName()]
            ]
        ];
        parent::__construct($config);
    }

    static function buildConditions($args, $field, &$conditions)
    {
        if (!empty($args['name'])) {
            if (!empty($args['from']) || !empty($args['to'])) {
                throw new Error("If either 'from' or 'to' is given, no 'name' must be supplied");
            }
            $range = DateTimeRange::byName($args['name']);
            $conditions["${field}[<>]"] = [
                $range->formatFrom(),
                $range->formatTo()
            ];
        } else if (!empty($args['from']) && !empty($args['to'])) {
            $conditions["${field}[<>]"] = [
                $args['from']->format(DateTime::ISO8601),
                $args['to']->format(DateTime::ISO8601)
            ];
        } else if (!empty($args['from'])) {
            $conditions["${field}[>=]"] = $args['from']->format(DateTime::ISO8601);
        } else if (!empty($args['to'])) {
            $conditions["${field}[<=]"] = $args['to']->format(DateTime::ISO8601);
        }
    }
}
