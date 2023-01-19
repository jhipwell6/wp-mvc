<?php

namespace WP_MVC\Traits;

use \League\Csv\Reader;
use \League\Csv\Writer;

if ( ! defined( 'ABSPATH' ) )
	exit;

trait CSV_Import_Export_Trait
{
	
	public function init_csv_importer( $post_type, $namespaced_model_name )
	{
		$this->post_type = $post_type;
		$this->model_name = $namespaced_model_name;
		$this->import_action_param = 'mvc_' . $this->post_type . '_csv_import';
		$this->export_action_param = 'mvc_' . $this->post_type . '_csv_export';
		$this->import_file_name = 'mvc_' . $this->post_type . '_csv_import_file';
		
		add_action( 'admin_head-edit.php', array( $this, 'add_action_buttons' ) );
		add_action( 'admin_init', array( $this, 'import_from_csv' ) );
		add_action( 'admin_init', array( $this, 'export_to_csv' ) );
	}
	
	public function import_from_csv()
	{
		$do_import = filter_input( INPUT_POST, $this->import_action_param );
		if ( $this->is_this_post_type_screen() && $do_import ) {
			$file = $_FILES[ $this->import_file_name ]['tmp_name'];
			$csv = Reader::createFromPath( $file, 'r' );
			$csv->setHeaderOffset( 0 );
			$records = $csv->getRecords();
			foreach ( $records as $offset => $record ) {
				$Item = $this->get_post_from_record( $record );
				$Item->set_props( $record );
				$Item = $Item->save();
			}
		}
	}
	
	public function export_to_csv()
	{
		$do_export = filter_input( INPUT_GET, $this->export_action_param );
		if ( $this->is_this_post_type_screen() && $do_export ) {
			$Class = $this->model_name;
			$Model = new $Class();
			$csv = Writer::createFromString();
			$csv->insertOne( $Model->get_property_keys() );

			$items = get_posts( array(
				'post_type' => $this->post_type,
				'posts_per_page' => -1
			) );
			if ( ! empty( $items ) ) {
				foreach ( $items as $item ) {
					$Item = new $Class( $item->ID ); 
					$csv->insertOne( $Item->to_csv_array() );
				}
				$csv->output( 'mvc-' . $this->post_type . '-data.csv' );
				die;
			}
		}
	}
	
	public function add_action_buttons()
	{
		if ( $this->is_this_post_type_screen() ) {			
			$export_url = add_query_arg( array(
				"{$this->export_action_param}" => 1
			) );
			ob_start();
			?>
			<a id="mvc-<?php echo $this->post_type; ?>-import-button" class="page-title-action thickbox" href="#TB_inline?&width=300&height=200&inlineId=mvc-<?php echo $this->post_type; ?>-import-data">Import</a>
			<a id="mvc-<?php echo $this->post_type; ?>-export-button" class="page-title-action" href="<?php echo $export_url; ?>">Export</a>
			<div id="mvc-<?php echo $this->post_type; ?>-import-data" style="display:none;">
				<h3>Import</h3>
				<form method="post" id="<?php echo $this->post_type; ?>-import-data-form" enctype="multipart/form-data">
					<input type="file" name="<?php echo $this->import_file_name; ?>" />
					<br />
					<input type="submit" name="<?php echo $this->import_action_param; ?>" class="button button-primary" value="Import" />
				</form>
			</div>
			<?php
			$output = ob_get_clean();
			?>
			<script type="text/javascript">
				( function ( $ ) {
					$( document ).ready( () => {
						$( 'hr.wp-header-end' ).before( '<?php echo str_replace( array( "\r", "\n", "\t" ), '', $output ); ?>' );
					} )
				}( jQuery ) );
			</script>
			<?php
			add_thickbox();
		}
	}
	
	private function get_post_from_record( $record )
	{
		$Class = $this->model_name;
		if ( isset( $record['id'] ) ) {
			return new $Class( $record['id'] );
		} else {
			$Model = new $Class();
			$unique_key = $record[ $Model->get_unique_key() ];
			return $Model->get_by_unique_key( $unique_key );
		}
	}
	
	private function is_this_post_type_screen()
	{
		global $pagenow;
		return ( 'edit.php' == $pagenow && isset( $_GET['post_type'] ) && $this->post_type == $_GET['post_type'] ) ? true : false;
	}
}