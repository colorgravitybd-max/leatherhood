<?php
declare(strict_types=1);

namespace LH\Services;

use LH\Core\Database;
use LH\Core\Helpers;

final class ShippingService
{
    /**
     * Compute the shipping fee for a given destination + subtotal.
     * Honors free-shipping threshold and police-station deliverability.
     *
     * @return array{fee:float, deliverable:bool, message:?string,
     *               district:?array, police_station:?array}
     */
    public static function quote(?int $districtId, ?int $policeStationId, float $subtotal): array
    {
        $threshold = (float) Helpers::setting('free_shipping_threshold', 5000);

        if (!$districtId) {
            return ['fee' => 0.0, 'deliverable' => false,
                    'message' => 'Please select a district.',
                    'district' => null, 'police_station' => null];
        }

        $district = Database::i()->one(
            'SELECT * FROM geo_districts WHERE id = ? LIMIT 1', [$districtId]);
        if (!$district) {
            return ['fee'=>0,'deliverable'=>false,'message'=>'District not found',
                    'district'=>null,'police_station'=>null];
        }
        if (!$district['is_deliverable']) {
            return ['fee'=>0,'deliverable'=>false,
                    'message'=>'Sorry, we don\'t deliver to '.$district['name'].' yet.',
                    'district'=>$district,'police_station'=>null];
        }

        $ps = null;
        $extra = 0.0;
        if ($policeStationId) {
            $ps = Database::i()->one(
                'SELECT * FROM geo_police_stations WHERE id = ? AND district_id = ? LIMIT 1',
                [$policeStationId, $districtId]);
            if (!$ps) {
                return ['fee'=>0,'deliverable'=>false,
                        'message'=>'Police station not found in selected district.',
                        'district'=>$district,'police_station'=>null];
            }
            if (!$ps['is_deliverable']) {
                return ['fee'=>0,'deliverable'=>false,
                        'message'=>'Sorry, we don\'t deliver to '.$ps['name'].' yet.',
                        'district'=>$district,'police_station'=>$ps];
            }
            $extra = (float)$ps['extra_shipping_fee'];
        }

        $fee = (float)$district['base_shipping_fee'] + $extra;

        if ($threshold > 0 && $subtotal >= $threshold) {
            $fee = 0.0;
        }

        return [
            'fee'           => $fee,
            'deliverable'   => true,
            'message'       => null,
            'district'      => $district,
            'police_station'=> $ps,
        ];
    }

    public static function divisions(): array
    {
        return Database::i()->all(
            'SELECT id, name, name_bn, slug FROM geo_divisions ORDER BY sort_order, name');
    }

    public static function districts(int $divisionId): array
    {
        return Database::i()->all(
            'SELECT id, name, name_bn, slug, base_shipping_fee, is_deliverable
               FROM geo_districts WHERE division_id = ? ORDER BY sort_order, name',
            [$divisionId]);
    }

    public static function policeStations(int $districtId): array
    {
        return Database::i()->all(
            'SELECT id, name, name_bn, slug, extra_shipping_fee, is_deliverable
               FROM geo_police_stations WHERE district_id = ? ORDER BY sort_order, name',
            [$districtId]);
    }
}
