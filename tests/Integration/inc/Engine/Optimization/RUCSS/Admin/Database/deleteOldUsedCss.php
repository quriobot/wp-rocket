<?php
declare(strict_types=1);

namespace WP_Rocket\Tests\Integration\inc\Engine\Optimization\RUCSS\Admin\Database;

use WP_Rocket\Tests\Integration\DBTrait;
use WP_Rocket\Tests\Integration\TestCase;

/**
 * @covers \WP_Rocket\Engine\Optimization\RUCSS\Admin\Database::delete_old_used_css
 *
 * @group  RUCSS
 */
class Test_DeleteOldUsedCss extends TestCase{
	use DBTrait;

	public static function setUpBeforeClass(): void {
		self::installFresh();

		parent::setUpBeforeClass();
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();

		self::uninstallAll();
	}

	public function tearDown() : void {
		remove_filter( 'pre_get_rocket_option_remove_unused_css', [ $this, 'set_rucss_option' ] );

		parent::tearDown();
	}

	public function testShouldTruncateTableWhenOptionIsEnabled(){
		$container           = apply_filters( 'rocket_container', null );
		$rucss_usedcss_table = $container->get( 'rucss_usedcss_table' );
		$rucss_usedcss_query = $container->get( 'rucss_used_css_query' );

		add_filter( 'pre_get_rocket_option_remove_unused_css', [ $this, 'set_rucss_option' ] );
		$current_date = current_time( 'mysql', true );
		$old_date     = date('Y-m-d H:i:s', strtotime( $current_date. ' - 1 month' ) );

		$rucss_usedcss_query->add_item(
			[
				'url'            => 'http://example.org/home',
				'css'            => 'h1{color:red;}',
				'unprocessedcss' => wp_json_encode( [] ),
				'retries'        => 3,
				'is_mobile'      => false,
				'last_accessed'  => $current_date,
			]
		);
		$rucss_usedcss_query->add_item(
			[
				'url'            => 'http://example.org/home',
				'css'            => 'h1{color:red;}',
				'unprocessedcss' => wp_json_encode( [] ),
				'retries'        => 3,
				'is_mobile'      => true,
				'last_accessed'  => $old_date,
			]
		);

		$result = $rucss_usedcss_query->query();

		$this->assertTrue( $rucss_usedcss_table->exists() );
		$this->assertCount( 2, $result );

		do_action( 'rocket_rucss_clean_rows_time_event' );

		$rucss_usedcss_query = $container->get( 'rucss_used_css_query' );
		$resultAfterTruncate = $rucss_usedcss_query->query();

		$this->assertCount( 1, $resultAfterTruncate );
	}

	public function set_rucss_option() {
		return true;
	}
}
