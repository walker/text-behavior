<?php
#rename this to app/models/behaviors/text.php
#rename classTextile.php from http://svn.textpattern.com/development/4.0/textpattern/lib/classTextile.php to app/vendors/textile.php
#put markdown.php from http://www.michelf.com/projects/php-markdown/ to app/vendors/markdown.php
#put var $actsAs = array('Text' => array('options' => array('fields' => array('content', 'excerpt', 'etc'), 'format' => 'textile'))); in your model
#in that last example replace 'textile' with 'markdown' if you wish to use markdown to format your text

	class TextBehavior extends ModelBehavior {
		#textile var
		var $Textile;
		
		function setup(&$model, $config = array('fields'=>array()))
		{
			$config['formats'] = array();
			foreach($config['fields'] as $key => $value)
			{
				if(false===$model->hasField($key))
				{
					unset($config['fields'][$key]);
					user_error('Model "'.$model->name.'" does not have a field called: '. $key, E_USER_ERROR );
				} else if(!in_array($value, $config['formats'])) {
					$config['formats'][] = strtolower($value);
				}
			
				if(false===$model->hasField('html_'.$key))
				{
					unset($config['fields'][$key]);
					user_error('Model "'.$model->name.'" does not have a field called: '.'html_'.$key, E_USER_ERROR ); 
				} else if(!in_array($value, $config['formats'])) {
					$config['formats'][] = strtolower($value);
				}
			}
			
			$settings = am(array(
				'enabled' => true,
				'safe' => true,
				'restricted' => false,
			), $config);
			
			$this->settings[$model->alias] = $settings;
		}

		/*
		 * beforeSave, takes the field info, sees if it's set in $this->tm->data (the model's data) and then processes it.
		 */
		function beforeSave(&$model)
		{
			extract($this->settings[$model->alias]);
			
			if (!$enabled) {
				return true;
			}
			
			foreach($formats as $format)
			{
				if($format=='textile') {
					vendor('textile');
					$this->Textile = @new Textile;
				}
				if($format=='markdown') { vendor('markdown'); }
			}
			
			foreach($fields as $key => $value)
			{
				if(isset($model->data[$model->name][$key]))
				{
					$text = $model->data[$model->name][$key];
					switch($value)
					{
						case 'textile':
							if($restricted)
							{
								$model->data[$model->name]['html_'.$key] = @$this->Textile->TextileRestricted($text);
							} else {
								$model->data[$model->name]['html_'.$key] = @$this->Textile->TextileThis($text);
							}
							break;
						case 'markdown':
							$model->data[$model->name]['html_'.$key] = Markdown($text);
							break;
						case 'plain':
							if($safe)
							{
								$model->data[$model->name]['html_'.$key] = strip_tags($text);
							}
							$model->data[$model->name]['html_'.$key] = str_replace("\r",'<br />',str_replace("\n",'<br />','<p>'.$model->data[$model->name]['html_'.$key].'</p>'));
							//$this->tm->data[$this->tm->name]['html_'.$key] = nl2br($this->tm->data[$this->tm->name]['html_'.$key]);
							break;
						default:
							$model->data[$model->name]['html_'.$key] = $text;
					}
				}
			}
			return true;
		}
}
?>