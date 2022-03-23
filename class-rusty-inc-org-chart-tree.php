<?php
ff
/**
 * Represents an org-chart tree
 */
class Rusty_Inc_Org_Chart_Tree {
	private $list_of_teams;

	/**
	 * @param array $list_of_teams an array of teams, where each team is an associative array with at least an `id` and `parent_id` keys
	*/
	public function __construct( $list_of_teams ) {
		$this->list_of_teams = $list_of_teams;
	}

	/**
	 * Converts the internal representation to a nested representation, for which:
	 * - each node is an associative array with at least the following keys:
	 *   - `id`
	 *   - `children`: an array of the children of the node, each of them a node by itself
	 * - the whole tree is represented by the root
	 *
	 * @return array|null the root of the tree or `null` if the tree is empty
	*/
	public function get_nested_tree( $root = null ) {
		//print_r($this->list_of_teams );
		if ( is_null( $root ) ) {
			$root = $this->get_root( $this->list_of_teams );
			if ( is_null( $root ) ) {
				return null;
			}
		}
		
		//
		$array_filter = [];
		foreach ($this->get_children( $root ) as $child) {
		//for($i = 0; $i < count($this->get_children( $root )); $i++){
			//print_r($child);
			//print_r($this->get_children( $root )[$i]);
			//$array_filter[] = $this->get_nested_tree( $this->get_children( $root )[$i] );
			$array_filter[] = $this->get_nested_tree( $child );
		}
		//print_r($array_filter);
		$root['children'] = $array_filter;
		//
		//exit;
		/*$root['children'] = array_map(
			function( $child ) {
				return $this->get_nested_tree( $child );
			},
			$this->get_children( $root )
		);*/
		return $root;
	}

	public function get_nested_tree_js( $root = null ) {
		//print_r($root);
		$root = $this->get_nested_tree( $root );
		if ( is_null( $root ) ) {
			return 'null';
		}
		
		$js = '{';
		foreach ( $root as $key => $value ) {
			$js .= '"' . $key . '":';
			if ( 'children' === $key ) {
				$js .= '[' . implode( ', ', array_map( [ $this, 'get_nested_tree_js' ], $value ) ) . ']';
			} elseif ( is_numeric( $value ) ) {
				$js .= $value . ',';
			} elseif ( 'emoji' === $key ) {
				$js .= $this->emoji_to_js( $value );
			} elseif ( null === $value ) {
				$js .= 'null,';
			} else {
				$js .= '"' . $value . '",';
			}
		}
		$js .= '}';

		//$js  = 1; //zeeshan
		return $js;
	}

	private function get_root( $tree ) {
		/*echo '<pre>';
		print_r($tree);
		//echo count($tree);
		exit;*/
		//for($i = 1; $i <= count($tree); $i++){
		foreach ( $tree as $team ) {
			//echo '____';
			//print_r($team);
			//print_r($tree[$i]);
			//echo '____'.$team['parent_id'];
			//echo $team['parent_id'];
			if ( is_null( $team['parent_id'] ) ) {
			//if ( is_null( $tree[$i]['parent_id'] ) ) {
				//echo '<pre>';
				//print_r($tree);
				return $team;
				//return $tree[$i];
			}
		}
		return null;
	}

	private function get_children( $parent ) {
		
		$array_filter = [];
		//print_r($this->list_of_teams);
		for($i = 1; $i <= count($this->list_of_teams); $i++){
		//foreach ($this->list_of_teams as $key => $var) {
			//print_r($var);
			//if ($this->list_of_teams[$key]['parent_id'] === $parent['id']) {
				//$array_filter[$key] = $var;
			if ($this->list_of_teams[$i]['parent_id'] === $parent['id']) {
				$array_filter[$i] = $this->list_of_teams[$i];
			}
		}
		
		/*echo '<pre>';
		print_r( $array_filter);
		echo '</pre>';
		exit;*/
		return array_values($array_filter);

		/*return array_values(
			array_filter(
				$this->list_of_teams,
				function( $team ) use ( $parent ) {
					
					return $team['parent_id'] === $parent['id'];
				}
			)
		);*/
	}

	private function emoji_to_js( $emoji ) {
		return '"' . implode(
			'',
			array_map(
				function( $utf16 ) {
					return '\u' . str_pad( strtolower( sprintf( '%X', $utf16 ) ), 4, '0', STR_PAD_LEFT );
				},
				$this->emoji_to_utf16_surrogate( $this->utf8_ord( $emoji ) )
			)
		) . '",';
	}

	private function emoji_to_utf16_surrogate( $emoji ) {
		if ( $emoji > 0x10000 ) {
			return [ ( ( $emoji - 0x10000 ) >> 10 ) + 0xD800, ( ( $emoji - 0x10000 ) % 0x400 ) + 0xDC00 ];
		} else {
			return [ $emoji ];
		}
	}

	private function utf8_ord( $emoji ) {
		$first_byte = ord( $emoji[0] );
		if ( $first_byte >= 0 && $first_byte <= 127 ) {
			return $first_byte;
		}
		$second_byte = ord( $emoji[1] );
		if ( $first_byte >= 192 && $first_byte <= 223 ) {
			return ( $first_byte - 192 ) * 64 + ( $second_byte - 128 );
		}
		$third_byte = ord( $emoji[2] );
		if ( $first_byte >= 224 && $first_byte <= 239 ) {
			return ( $first_byte - 224 ) * 4096 + ( $second_byte - 128 ) * 64 + ( $third_byte - 128 );
		}
		$fourth_byte = ord( $emoji[3] );
		if ( $first_byte >= 240 && $first_byte <= 247 ) {
			return ( $first_byte - 240 ) * 262144 + ( $second_byte - 128 ) * 4096 + ( $third_byte - 128 ) * 64 + ( $fourth_byte - 128 );
		}
		return false;
	}
}
