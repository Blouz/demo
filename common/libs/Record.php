<?php
/**
 * 积分处理操作类
 *
 * PHP Version 5
 *
 * @category  WAP
 * @package   描述
 * @author    renyineng <renyineng@iyangpin.com>
 * @time      15-12-11 下午1:57
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      renyineng@iyangpin.com
 */
namespace common\libs;

use yii\base\ErrorException;
use yii\base\Object;
use yii\db\Exception;
use yii\db\Expression;

class Record extends Object
{
    public $db;
    public $log_table = 'i500_score_log';
    public $user_table = 'i500_user_basic_info';
    public $data = [];
    public static $getMapping = [
        'score'
    ];
    public function __construct($data)
    {
        if (empty($data)) {
            throw new ErrorException("无效的数据");
        }
        $this->data = $data;
        $this->db = \Yii::$app->db_social;
    }
    public function score()
    {
        $transaction = $this->db->beginTransaction();
        try {
            $this->db->createCommand()->insert($this->log_table, $this->data)->execute();
            $this->db->createCommand()->update($this->user_table,
                [
                    'score'=>new Expression('score+'.$this->data['score']),
                ],['mobile'=>$this->data['mobile']]
            )->execute();
            $transaction->commit();
        } catch(Exception $e) {
            $transaction->rollBack();
        }
    }

}