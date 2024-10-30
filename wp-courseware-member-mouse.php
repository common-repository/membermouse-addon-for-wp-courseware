<?php
/*
 * Plugin Name: WP Courseware - MemberMouse Add On
 * Version: 1.10
 * Plugin URI: http://flyplugins.com
 * Description: The official extension for <strong>WP Courseware</strong> to add support for the <strong>MemberMouse membership plugin</strong> for WordPress.
 * Author: Fly Plugins
 * Author URI: http://flyplugins.com
 */

// Main parent class
include_once 'class_members.inc.php';

function wpcw_mm_db_cleanup() {
	global $wpdb;
	$bundle_list = MM_Bundle::getBundlesList();

	if ( ! empty( $bundle_list ) ) {
		foreach ( $bundle_list as $bundle_id => $bundle_name ) {
			$new_bundle_id = "b" . $bundle_id;

			$do_it = $wpdb->get_var( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpcw_member_levels SET member_level_id = '%s' WHERE member_level_id = '%d'", $new_bundle_id, $bundle_id ) );
		}
	}
}
register_activation_hook( __FILE__, 'wpcw_mm_db_cleanup' );

/**
 * Hook to load the class
 * Set to priority of 1 so that it works correctly with MemberMouse
 * that specifically needs this to be a priority of 1.
 */
add_action( 'init', 'WPCW_Members_MemberMouse_init', 1 );

/**
 * Initialise the membership plugin, only loaded if WP Courseware
 * exists and is loading correctly.
 */
function WPCW_Members_MemberMouse_init() {
	$item = new WPCW_Members_MemberMouse();

	// Check for WP Courseware
	if ( ! $item->found_wpcourseware() ) {
		$item->attach_showWPCWNotDetectedMessage();

		return;
	}

	// Not found the membership tool
	if ( ! $item->found_membershipTool() ) {
		$item->attach_showToolNotDetectedMessage();

		return;
	}

	// Found the tool and WP Coursewar, attach.
	$item->attachToTools();
}

/**
 * Membership class that handles the specifics of the MemberMouse WordPress plugin and
 * handling the data for levels for that plugin.
 */
class WPCW_Members_MemberMouse extends WPCW_Members {

	const GLUE_VERSION = 1.00;
	const EXTENSION_NAME = 'MemberMouse';
	const EXTENSION_ID = 'WPCW_members_membermouse';

	/**
	 * Main constructor for this class.
	 */
	public function __construct() {
		// Initialise using the parent constructor
		parent::__construct( WPCW_Members_MemberMouse::EXTENSION_NAME, WPCW_Members_MemberMouse::EXTENSION_ID, WPCW_Members_MemberMouse::GLUE_VERSION );
	}

	/**
	 * Get the membership levels for this specific membership plugin. (id => array (of details))
	 */
	protected function getMembershipLevels() {
		$bundle_list = MM_Bundle::getBundlesList();

		$member_list = MM_MembershipLevel::getMembershipLevelsList( $activeStatusOnly = true );

		$levelDataStructured = array();

		if ( ! empty( $bundle_list ) ) {
			// Format the data in a way that we expect and can process
			foreach ( $bundle_list as $bundleID => $bundleName ) {
				$levelItem         = array();
				$levelItem['name'] = $bundleName . ' - Bundle';
				$levelItem['id']   = 'b' . $bundleID;
				//$levelItem['raw'] 	= array($bundleID => $bundleName);

				$levelDataStructured[ $levelItem['id'] ] = $levelItem;
			}
		}

		if ( ! empty( $member_list ) ) {
			foreach ( $member_list as $membership_level => $level_name ) {
				$levelItem                               = array();
				$levelItem['name']                       = $level_name . ' - Membership Level';
				$levelItem['id']                         = 'm' . $membership_level;
				$levelDataStructured[ $levelItem['id'] ] = $levelItem;
			}
		}

		if ( ! empty( $levelDataStructured ) ) {
			return $levelDataStructured;
		}

		return false;
	}

	/**
	 * Function called to attach hooks for handling when a user is updated or created.
	 */
	protected function attach_updateUserCourseAccess() {
		// Events called whenever the user levels are changed, which updates the user access.
		add_action( 'mm_member_add', array( $this, 'handle_updateUserCourseAccess' ), 10, 1 );
		add_action( 'mm_member_membership_change', array( $this, 'handle_updateUserCourseAccess' ), 5, 1 );
		add_action( 'mm_member_delete', array( $this, 'handle_updateUserCourseAccess' ), 10, 1 );
		add_action( 'mm_bundles_add', array( $this, 'handle_updateUserCourseAccess' ), 10, 1 );
		add_action( 'mm_bundles_status_change', array( $this, 'mm_check_status_to_de_enroll' ), 10, 1 );
		add_filter( 'wpcw_courses_canuseraccesscourse', array( $this, 'mm_check_wpcw_course_access' ), 10, 3 );
	}

	/**
	 * Assign selected courses to members of a paticular level.
	 *
	 * @param Level ID in which members will get courses enrollment adjusted.
	 */
	protected function retroactive_assignment( $level_ID ) {
		global $wpdb;

		$page = new PageBuilder( false );

		$check_level_type = substr( $level_ID, 0, 1 );

		$membership_type_id = substr( $level_ID, 1 );

		$bundle_list = MM_Bundle::getBundlesList();

		//Query for users that belong to membership $level_ID
		if ( $check_level_type === 'm' ) {
			$SQL = "SELECT wp_user_id
			FROM {$wpdb->prefix}mm_user_data
			WHERE membership_level_id = $membership_type_id";

			$members = $wpdb->get_results( $SQL, ARRAY_N );
			//$member_id = 'wp_user_id';
		} else { //OR Query for users that have a bundle $level_ID

			$SQL = "SELECT access_type_id
			FROM {$wpdb->prefix}mm_applied_bundles
			WHERE access_type = 'user' AND bundle_id = $membership_type_id";

			$members = $wpdb->get_results( $SQL, ARRAY_N );
			//$member_id = 'access_type_id';
		}
		//Do we have members?
		if ( $members ) {
			//Get array of bundles and membership level to apply to member
			foreach ( $members as $memberArray ) {
				foreach ( $memberArray as $member_id ) {
					//Get MM user object
					$user = new MM_User( $member_id );
					//Initialize user level array
					$userLevels = array();
					//Get user applied bundles
					$appliedBundles = $user->getAppliedBundles();
					//Get user membership level
					$membershipLevelID = $user->getMembershipID();
					//If user has bundles generate array of the bundle IDs to apply.
					if ( $appliedBundles ) {
						foreach ( $appliedBundles as $appliedBundle ) {
							$userLevels[] = 'b' . $appliedBundle->getBundleId();
						}

						// Add membership level to array
						$userLevels[] = 'm' . $membershipLevelID;
					} else {
						// Add membership level to array
						$userLevels[] = 'm' . $membershipLevelID;
						// Check for bundles applied to membership levels
						foreach ( $bundle_list as $bundle_id => $bundle ) {
							//wp_set_current_user($member[$member_id]);
							$checkForAssociatedBundle = mm_member_decision( array( 'membershipId' => $membershipLevelID, 'hasBundle' => $bundle_id ) );
							//If bundle is associated with membership level add it to array
							if ( $checkForAssociatedBundle ) {
								$userLevels[] = 'b' . $bundle_id;
							}
						}
					}
					//Sync up the courses for this user
					parent::handle_courseSync( $member_id, $userLevels );
				}
			}

			$page->showMessage( __( 'All members were successfully retroactively enrolled into the selected courses.', 'wp_courseware' ) );

			return;
		} else {
			$page->showMessage( __( 'No existing customers found for the specified level/bundle.', 'wp_courseware' ) );
		}
	}

	/**
	 * Function just for handling the membership callback, to interpret the parameters
	 * for the class to take over.
	 *
	 * @param Array $memberDetails The details if the user being changed.
	 */
	public function handle_updateUserCourseAccess( $memberDetails ) {
		// Get all of the levels for this user.
		$user = new MM_User( $memberDetails['member_id'] );

		$userLevels = array();

		// $bundle_list = MM_Bundle::getBundlesList();

		if ( $user->isValid() ) {
			// ### 1 - Only get active bundles
			$appliedBundles = $user->getAppliedBundles();

			//$userMemberType = new MM_MembershipLevel($user->getMembershipId());
			$userMemberType = $user->getMembershipId();

			if ( $appliedBundles ) {
				foreach ( $appliedBundles as $appliedBundle ) {
					// Generate a list of the bundle IDs to apply.
					$userLevels[] = 'b' . $appliedBundle->getBundleId();
				}
				//$key = count($appliedBundles);
				$userLevels[] = 'm' . $userMemberType;
			} else {
				$userLevels[] = 'm' . $userMemberType;
			}
		}

		// Over to the parent class to handle the sync of data.
		parent::handle_courseSync( $memberDetails['member_id'], $userLevels );
	}

	/**
	 * Function to check status for de-enrollment.
	 *
	 * @param $memberDetails
	 */
	public function mm_check_status_to_de_enroll( $memberDetails ) {
		global $wpcwdb, $wpdb;

		// Get Vital Details about membership.
		$bundle_status = $memberDetails['bundle_status'] ?: false;
		$bundle_id     = $memberDetails['bundle_id'] ?: false;
		$user_id       = $memberDetails['member_id'] ?: false;

		if ( 1 == $bundle_status ) {
			$this->handle_updateUserCourseAccess( $memberDetails );
		}

		// Only if status is cancellation and we have a user id.
		if ( 2 != $bundle_status || ! $user_id ) {
			return;
		}

		// Get Details.
		$user           = new MM_User( $user_id );
		$appliedBundles = $user->getAppliedBundles( true );
		$membershipID   = $user->getMembershipId();
		$status         = array( MM_Status::$CANCELED );

		// Check if there is applied Bundles.
		if ( ! $appliedBundles ) {
			return;
		}

		// Get Membership Courses.
		$membership_courses = $wpdb->get_col( $wpdb->prepare(
			"SELECT course_id 
			 FROM $wpcwdb->map_member_levels
			 WHERE member_level_id = %s",
			'm' . $membershipID
		) );

		// Get Member Data.
		$wpcw_member_data   = new WPCW_Members_MemberMouse();
		$getAttachedCourses = $wpcw_member_data->getCourseAccessListForLevel( 'b' . $bundle_id );

		// Access Arrays.
		$access_granted       = array();
		$access_denied        = array();
		$courses_to_de_enroll = array();

		// Check applied bundles.
		foreach ( $appliedBundles as $appliedBundle ) {
			$bundle_id = $appliedBundle->getBundleId();

			$bundle_courses = $wpdb->get_col( $wpdb->prepare(
				"SELECT course_id 
				     FROM $wpcwdb->map_member_levels
					 WHERE member_level_id = %s",
				'b' . $bundle_id
			) );

			if ( $bundle_courses ) {
				foreach ( $bundle_courses as $bundle_course_id ) {
					if ( $appliedBundle->getStatus() == MM_Status::$CANCELED ) {
						$access_denied[] = $bundle_course_id;
					} else {
						$access_granted[] = $bundle_course_id;
					}
					break;
				}
			}
		}

		// Check attached courses.
		foreach ( $getAttachedCourses as $courseID => $bundleID ) {
			if ( in_array( $courseID, $access_denied ) && in_array( $courseID, $access_granted ) ) {
				$access_granted[] = $courseID;
			}

			if ( in_array( $courseID, $membership_courses ) ) {
				$access_granted[] = $courseID;
			}

			if ( ! in_array( $courseID, $access_granted ) ) {
				$courses_to_de_enroll[] = $courseID;
			}
		}

		// De-Enroll.
		if ( ! empty( $courses_to_de_enroll ) ) {
			wpcw()->enrollment->unenroll_student( $user_id, $courses_to_de_enroll );
		}

		return;
	}

	/**
	 * Function just for checking membership and bundle status
	 * to determine whether or not to display course content.
	 *
	 * @param $access
	 * @param $courseID
	 * @param $userID
	 *
	 * @return bool
	 */
	public function mm_check_wpcw_course_access( $access, $courseID, $userID ) {
		global $wpcwdb, $wpdb;

		$user_id = get_current_user_id();

		if ( $user_id ) {
			$user           = new MM_User( $user_id );
			$appliedBundles = $user->getAppliedBundles( true );
			$membershipID   = $user->getMembershipId();
			$status         = array(
				MM_Status::$CANCELED,
				MM_Status::$LOCKED,
				MM_Status::$PAUSED,
				MM_Status::$OVERDUE,
				MM_Status::$ERROR,
				MM_Status::$EXPIRED,
			);

			$membership_courses = $wpdb->get_col( $wpdb->prepare(
				"SELECT course_id 
				 FROM $wpcwdb->map_member_levels
				 WHERE member_level_id = %s",
				'm' . $membershipID
			) );

			if ( in_array( $user->getStatus(), $status ) ) {
				if ( $membership_courses ) {
					foreach ( $membership_courses as $membership_course_id ) {
						if ( $membership_course_id === $courseID ) {
							return false;
						}
					}
				}
			}

			if ( $appliedBundles ) {
				$access_granted = array();
				$access_denied  = array();

				foreach ( $appliedBundles as $appliedBundle ) {
					$bundle_id = $appliedBundle->getBundleId();

					$bundle_courses = $wpdb->get_col( $wpdb->prepare(
						"SELECT course_id 
						 FROM $wpcwdb->map_member_levels
						 WHERE member_level_id = %s",
						'b' . $bundle_id
					) );

					if ( $bundle_courses ) {
						foreach ( $bundle_courses as $bundle_course_id ) {
							if ( $courseID === $bundle_course_id ) {
								if ( in_array( $appliedBundle->getStatus(), $status ) ) {
									$access_denied[] = $courseID;
								} else {
									$access_granted[] = $courseID;
								}
								break;
							}
						}
					}
				}

				if ( ! in_array( $courseID, $access_denied ) && ! in_array( $courseID, $access_granted ) ) {
					$access_granted[] = $courseID;
				}

				if ( in_array( $courseID, $membership_courses ) ) {
					$access_granted[] = $courseID;
				}

				if ( ! in_array( $courseID, $access_granted ) ) {
					$access = false;
				}
			}
		}

		return $access;
	}

	/**
	 * Detect presence of the membership plugin.
	 */
	public function found_membershipTool() {
		return class_exists( 'MM_Bundle' );
	}
}
