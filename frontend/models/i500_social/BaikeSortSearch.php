<?php
/**
 * 百科的分类使用记录表
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    wyy <wyy@iyangpin.com>
 * @time      2017/05/08
 * @copyright 2015 灵韬致胜（北京）科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      wyy@i500m.com
 */

namespace frontend\models\i500_social;

class BaikeSortSearch extends SocialBase
{
    /**
     * 设置表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%i500_baike_sort_search}}';
    }
}
