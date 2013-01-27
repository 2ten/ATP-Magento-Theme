<?php

class Atp_ThemeEnhacements_Model_AddThis_Buttons extends AddThis_SharingTool_Model_Source_Buttons
{
    public function toOptionArray()
    {
      $result = parent::toOptionArray();
      $result[] = array('value'=>'style_atp','label'=>'&nbsp;&nbsp;ATP Style');
      return $result;
    }
}