<?php
/**
 * 群成员groupmember model
 *
 * PHP Version 5
 *
 * @category  MODEL
 * @package   Social
 * @author    duzongyan <duzongyan@i500m.com>
 * @time      2017-04-18
 * @copyright 辽宁i500科技发展有限公司
 * @license   http://www.i500m.com license
 * @link      duzongyan@i500m.com
 */
namespace frontend\models\i500_social_rewrite;

use Yii;

class GroupMember extends SocialRewriteBase
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'i500_group_member';
    }
}
