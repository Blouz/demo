<?php

namespace frontend\models\i500_social_rewrite;

class ActivityComment extends SocialRewriteBase
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%i500_activity_comments}}';
    }
    
    public function getPhoto()
    {
        return $this->hasMany(ActivityCommentsPhoto::className(), ['activity_comments_id'=>'id']);
    }
}
