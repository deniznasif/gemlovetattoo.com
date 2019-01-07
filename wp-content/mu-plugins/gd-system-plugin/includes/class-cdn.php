<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class CDN {

	private static $file_ext = [ '7z', 'aac', 'ai', 'asf', 'avi', 'bmp', 'bz2', 'css', 'doc', 'docx', 'eot', 'eps', 'fla', 'flv', 'gif', 'gz', 'ico', 'indd', 'jpeg', 'jpg', 'js', 'm4a', 'm4v', 'mkv', 'mov', 'mp3', 'mp4', 'mpeg', 'mpg', 'oga', 'ogg', 'ogv', 'ogx', 'otf', 'pdf', 'png', 'ppt', 'pptx', 'psd', 'rar', 'rtf', 's7z', 'svg', 'svgz', 'tar', 'tgz', 'tiff', 'ttf', 'txt', 'wav', 'webp', 'woff', 'woff2', 'xls', 'xlsx', 'xml', 'zip', 'zipx' ];

	private $base_url;

	private $pattern;

	public function __construct() {

		if ( ! self::is_enabled() ) {

			return;

		}

		$this->base_url = sprintf( 'https://secureservercdn.net/%s/%s', GD_VIP, GD_TEMP_DOMAIN );

		$hosts = [
			filter_input( INPUT_SERVER, 'HTTP_HOST' ),
			wp_parse_url( home_url(), PHP_URL_HOST ),
			wp_parse_url( site_url(), PHP_URL_HOST ),
			GD_TEMP_DOMAIN,
		];

		$this->pattern = sprintf(
			'~(?:(?<=\'|")|(?:(?:https?:)?//(?:%s)))/(.+?\.(?:%s))~i',
			implode( '|', array_map( 'preg_quote', array_unique( array_filter( $hosts ) ) ) ),
			implode( '|', self::get_file_ext() )
		);

		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
		add_action( 'wp_head',           [ $this, 'wp_head' ], 2 );

		add_filter( 'wp_get_attachment_url', [ $this, 'wp_get_attachment_url' ] );

	}

	private static function get_file_ext() {

		return (array) apply_filters( 'wpaas_cdn_file_ext', self::$file_ext );

	}

	public static function is_enabled() {

		$vip         = defined( 'GD_VIP' )         ? GD_VIP         : null;
		$temp_domain = defined( 'GD_TEMP_DOMAIN' ) ? GD_TEMP_DOMAIN : null;
		$enabled     = defined( 'GD_CDN_ENABLED' ) ? GD_CDN_ENABLED : false;
		$enabled     = (bool) apply_filters( 'wpaas_cdn_enabled', $enabled );

		return ( $vip && $temp_domain &&  $enabled && self::get_file_ext() && ! WP_DEBUG && ! is_admin() );

	}

	public function template_redirect() {

		ob_start( function ( $content ) {

			return preg_replace( $this->pattern, "{$this->base_url}/$1", $content );

		} );

	}

	public function wp_head() {

		$url = wp_parse_url( $this->base_url );

		if ( ! empty( $url['scheme'] ) && ! empty( $url['host'] ) ) {

			printf( // xss ok.
				"<link rel='preconnect' href='%s://%s' />\n",
				$url['scheme'],
				$url['host']
			);

		}

	}

	public function wp_get_attachment_url( $url ) {

		return ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ? $url : preg_replace( $this->pattern, "{$this->base_url}/$1", $url );

	}

}
