<?php
require_once 'Record.php';

/**
 *
 */
class Avails extends Record
{
  protected static $tableName = "avails";
  protected static $primaryKey = "recid";

  static function custom_get_sql($options) {
    $date = new DateTime('-365 days');
    $date = $date->format('Y-m-d H:i:s');

    $where = " AND av.AvailDate > DATE('$date')";

    if (isset($options['all']) && $options['all'] == true ) {
      $where = '';
    }

    if (isset($options['id'])) {
      $where .= sprintf(' AND av.recid=%u', $options['id']);
    }

    $sql = "SELECT
      av.recid,
      av.AvailDate,
      av.Status,
      sp.SupplierName,
      av.SupplierID,
      tb.BrandName,
      tb.Quality,
      av.BrandID,
      av.ModelID,
      tp.TirePattern,
      av.PatternID,
      av.CondCode,
      ts.TireSize,
      av.SizeID,
      pr.PlyRating,
      av.PlyRatingID,
      tc.TRACode,
      av.TRACodeID,
      -- fl.FOBLocation,
      -- av.FOBLocID,
      av.FOBLocation,
      av.Quantity,
      av.Cost,
      av.Cost * av.Quantity as TotalCost,
      av.Comments,
      rc.Compound as RubberCompound,
      av.RubberCompoundID,
      av.PackageName,
      av.Currency,
      av.HasNumbers,
      sp.ContactName,
      sp.Phone1
      FROM avails av
      LEFT JOIN suppliers sp
        ON av.SupplierID = sp.recid
      LEFT JOIN tirebrands tb
        ON av.BrandID = tb.recid
      LEFT JOIN tiremodels tm
        ON av.ModelID = tm.recid
      LEFT JOIN tirepatterns tp
        ON av.PatternID = tp.recid
      LEFT JOIN tiresizes ts
        ON av.SizeID = ts.recid
      LEFT JOIN plyratings pr
        ON av.PlyRatingID = pr.recid
      LEFT JOIN tracodes tc
        ON av.TRACodeID = tc.recid
      LEFT JOIN foblocations fl
        ON av.FOBLocID = fl.recid
      LEFT JOIN rubbercompounds rc
        ON av.RubberCompoundID = rc.recid
      WHERE 1 $where
      ORDER BY av.recid DESC";

    return $sql;
  }

  static function custom_get_one_sql($id) {
    $sql = static::custom_get_sql([
      'id' => $id
    ]);
    return sprintf($sql, $id);
  }

  static function get_inventory() {

  }

}

?>
