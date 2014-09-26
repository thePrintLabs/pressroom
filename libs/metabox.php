<?php
class TPL_Metabox
{
   public $id;
   public $title;
   public $context;
   public $priority;
   public $fields;

   public function __construct($id, $title, $context, $priority, $fields = array()) {
      $this->id = $id;
      $this->title = $title;
      $this->context = $context;
      $this->priority = $priority;
      $this->fields = $fields;
   }

   /**
    * Add a metabox field
    * @param array $f - metabox field
    * @void
    */
   public function add_field( $id, $name, $description, $type, $default, $extra = array() ) {
      $params = array(
         'id'  	 => $id,
         'name'	 => $name,
         'desc' 	 => $description,
         'type'	 => $type,
         'default' => $default
      );
      $field = array_merge($params, $extra);
      array_push( $this->fields, $field);
   }

   /**
    * Create a metabox field
    * @param int $post_id - post ID
    * @param array $f - metabox field
    * @return string html field
    */
   public static function metabox_field_to_html( $post_id, $field ) {
      $f = (object)$field;
      $meta = get_post_meta( $post_id, $f->id, true);

      $html = '<tr>
      <th style="width:20%"><label for="' . $f->id . '">' . $f->name . '</label></th>
      <td>';

      switch ($f->type) {

         case 'text':
            $html.= '<input type="text" name="' . $f->id . '" id="' . $f->id . '" value="' . ( $meta ? $meta : $f->default ) . '" size="30" style="width:97%" /><br>'. $f->desc;
            break;

         case 'textarea':
            $html.= '<textarea name="' . $f->id . '" id="' . $f->id . '" cols="60" rows="4" style="width:97%">' . ( $meta ? $meta : $f->default ) . '</textarea><br>'. $f->desc;
            break;

         case 'select':
            $html.= '<select name="' . $f->id . '" id="' . $f->id . '">';
            foreach ( $f->options as $option ) {
               $html.= '<option '. ( $meta == $option ? 'selected="selected"' : '' ) . '>'. $option . '</option>';
            }
            $html.= '</select>';
            break;

         case 'select_multiple':
            $html.= '<select multiple name="' . $f->id . '[]" id="' . $f->id . '">';
            foreach ( $f->options as $option ) {
               $html.= '<option '. ( in_array( $option, $meta ) ? 'selected="selected"' : '' ) . '>'. $option . '</option>';
            }
            $html.= '</select>';
            break;

         case 'radio':
            foreach ( $f->options as $i => $option ) {
               $html.= ' <input type="radio" id="' . $f->id . '_' . $i .'" name="' . $f->id . '" value="' . $option['value'] . '" ' . ( $meta == $option['value'] ? 'checked="checked"' : '' ) . ' /> <label for="' . $f->id . '_' . $i . '">' . $option['name'] . '</label>';
            }
            break;

         case 'checkbox':
            $html.= '<input type="checkbox" name="' . $f->id . '" id="' . $f->id . '" ' . ( $meta ? 'checked="checked"' : '' ) . ' />';
            break;

         case 'file':
            $html.= '<input type="file" name="' . $f->id . '" id="' . $f->id . '" /><br>';
            if ($meta) {
               $url = wp_get_attachment_image_src($meta);
               if ($url) {
                  $html.= '<img height="200" src="'.$url[0].'">';
               }
            }
            break;

         case 'date':
            $html.= '<input type="text" name="' . $f->id . '" id="' . $f->id . '" value="'. ( $meta ? $meta : $f->default ) . '" size="30" style="width:30%" /><br>'. $f->desc;
            break;
      }

      $html.= '<td></tr>';
      return $html;
   }
}
