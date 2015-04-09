<?php
// encoding: utf-8
// vim: ft=php noexpandtab shiftwidth=4 tabstop=4

class Manage_DownloadsTest extends WP_UnitTestCase
{
	/**
	 * @test
	 */
	function plugins_loaded()
	{
		$this->assertTrue( shortcode_exists( 'download_counter' ) );
		$this->assertTrue( shortcode_exists( 'download_link' ) );
	}

	/**
	 * @test
	 */
	function init()
	{
		$this->assertTrue( post_type_exists( Manage_Downloads::get_post_type() ) );
	}

	/**
	 * @test
	 */
	function get_url_meta_box_01()
	{
		$post_id = $this->factory->post->create( array( 'post_type' => Manage_Downloads::get_post_type() ) );
		Manage_Downloads::set_download_url( $post_id, 'http://example.com/hello%20world' );

		$manage_downloads = new Manage_Downloads();
		$this->expectOutputString( '<input type="text" name="download-item-url" value="http://example.com/hello%20world" style="width: 100%;" placeholder="http://" />' );
		$manage_downloads->get_url_meta_box( get_post( $post_id ) );
	}

	/**
	 * @test
	 */
	function get_url_meta_box_02()
	{
		$post_id = $this->factory->post->create( array( 'post_type' => Manage_Downloads::get_post_type() ) );

		$manage_downloads = new Manage_Downloads();
		$this->expectOutputString( '<input type="text" name="download-item-url" value="" style="width: 100%;" placeholder="http://" />' );
		$manage_downloads->get_url_meta_box( get_post( $post_id ) );
	}

	/**
	 * @test
	 */
	function get_permalink()
	{
		$post_id = $this->factory->post->create( array( 'post_type' => Manage_Downloads::get_post_type() ) );

		$this->assertSame(
			'http://example.org/download/' . $post_id,
			Manage_Downloads::get_permalink( $post_id )
		);

		$this->assertEquals(
			'',
			Manage_Downloads::get_permalink( 'aa' )
		);

		$this->assertEquals(
			'',
			Manage_Downloads::get_permalink( '' )
		);
	}

	/**
	 * @test
	 */
	function get_permalink_with_filter()
	{
		$post_id = $this->factory->post->create( array( 'post_type' => Manage_Downloads::get_post_type() ) );

		add_filter( 'manage_downloads_rewrite_endpoint', function(){
			return 'foo';
		} );

		$this->assertSame(
			'http://example.org/foo/' . $post_id,
			Manage_Downloads::get_permalink( $post_id )
		);
	}

	/**
	 * @test
	 */
	function download_link()
	{
		$post_id = $this->factory->post->create( array(
			'post_type' => Manage_Downloads::get_post_type(),
			'post_title' => 'Download!!'
		) );

		$this->assertSame(
			'<a href="http://example.org/download/' . $post_id . '">Download!!</a>',
			do_shortcode( '[download_link id="' . $post_id . '"]' )
		);

		// illegal id
		$this->assertSame(
			'',
			do_shortcode( '[download_link id="99"]' )
		);

		// illegal id
		$this->assertSame(
			'',
			do_shortcode( '[download_link id="aaa"]' )
		);

		// illegal id
		$this->assertSame(
			'',
			do_shortcode( '[download_link id=""]' )
		);
	}

	/**
	 * @test
	 */
	function download_counter()
	{
		$post_id = $this->factory->post->create( array(
			'post_type' => Manage_Downloads::get_post_type(),
		) );

		$this->assertSame(
			"0",
			do_shortcode( '[download_counter id="' . $post_id . '"]' )
		);

		$this->assertSame(
			"0",
			do_shortcode( '[download_counter id="9999"]' )
		);

		update_post_meta( $post_id, 'download-item-counter', 999 );
		$this->assertSame(
			"999",
			do_shortcode( '[download_counter id="' . $post_id . '"]' )
		);
	}

	/**
	 * @test
	 */
	function get_download_url()
	{
		$post_id = $this->factory->post->create( array( 'post_type' => Manage_Downloads::get_post_type() ) );
		Manage_Downloads::set_download_url( $post_id, 'http://example.com/path/to/download' );

		$this->assertSame(
			'http://example.com/path/to/download',
			Manage_Downloads::get_download_url( $post_id )
		);
	}

	/**
	 * @test
	 */
	function get_count()
	{
		$post_id_01 = $this->factory->post->create( array( 'post_type' => Manage_Downloads::get_post_type() ) );
		$post_id_02 = $this->factory->post->create( array( 'post_type' => Manage_Downloads::get_post_type() ) );

		update_post_meta( $post_id_01, 'download-item-counter', 11 );
		update_post_meta( $post_id_02, 'download-item-counter', 22 );

		$this->assertSame( 11, Manage_Downloads::get_count( $post_id_01 ) );
		$this->assertSame( 22, Manage_Downloads::get_count( $post_id_02 ) );
		$this->assertSame( 33, Manage_Downloads::get_count( $post_id_01 . ',' . $post_id_02 ) );
	}
}
