=== WP Courseware for MemberMouse ===
Contributors: flyplugins
Donate link: https://flyplugins.com/donate
Tags: learning management system, course builder
Requires at least: 4.8
Tested up to: 6.5
Stable tag: 1.10
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Add integration between MemberMouse and WP Courseware which allows you to associate course(s) to membership levels and bundles for automatic enrollment.

== Description ==
[Fly Plugins](https://flyplugins.com) presents [WP Courseware](https://flyplugins.com/wp-courseware) for [MemberMouse](https://flyplugins.com/membermouse).

= Would you like to sell an online course with MemberMouse? =
The MemberMouse Addon for WP Courseware will add full integration with WP Courseware. Simply assign WP Courseware course(s) to a MemberMouse membership level or bundle. When a student purchases the membership level or bundle, they will automatically be enrolled into the associated course(s).

With this addon, you will be able to create a fully automated [Learning Management System](https://flyplugins.com/wp-courseware) and sell online courses.

= MemberMouse Plugin Integration with WP Courseware Plugin =
[youtube https://www.youtube.com/watch?v=aMjD_868Kr4]

= Basic Configuration Steps =
1. Create a course with WP Courseware and add modules, units, and quizzes
2. Create a course outline page using [shortcode]
3. Create a membership level and set a price
4. Associate one or more WP Courseware courses with the membership level or bundle
5. New student pays for the membership level or bundle, and WP Courseware enrolls them to the appropriate course(s) based on the purchased membership level or bundle

= Check out Fly Plugins =
For more tools and resources for selling online courses check out:

* [WP Courseware](https://flyplugins.com/wp-courseware/) - The leading learning management system for WordPress. Create and sell online courses with a drag and drop interface. It’s that easy!
* [S3 Media Maestro](https://flyplugins.com/s3-media-maestro) - The most secure HTML 5 media player plugin for WordPress with full AWS (Amazon Web Services) S3 and CloudFront integration.

= Follow Fly Plugins =
* [Facebook](https://facebook.com/flyplugins)
* [YouTube](https://www.youtube.com/flyplugins)
* [Twitter](https://twitter.com/flyplugins)
* [Instagram](https://www.instagram.com/flyplugins/)
* [LinkedIn](https://www.linkedin.com/company/flyplugins)

= Disclaimer =
This plugin is only the integration, or “middle-man” between WP Courseware and MemberMouse.

== Installation ==

1. Upload the `MemberMouse for WP Courseware addon` folder into the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently asked questions ==

= Does this plugin require WP Courseware to already be installed =

Yes!

= Does this plugin require MemberMouse to already be installed =

Yes!

= Where can I get WP Courseware? =

[WP Courseware](https://flyplugins.com/wp-courseware)

= Where can I get MemberMouse? =

[MemberMouse](https://flyplugins.com/membermouse).

== Screenshots ==

1. The Course Access Settings screen will display the courses associated with membership levels and bundles

2. This is the screen where specific courses are selected to be associated with the membership level or bundle. The retroactive function will enroll students to courses that were recently associated to the membership level or bundle.

== Changelog ==

= 1.10 =
* Fix: Fixed issue where table prefix was not declared causing database error.

= 1.9 =
* Fix: When a bundle is manually reactivated the student will automatically be re-enrolled.

= 1.8 =
* New: Added a function to de-enroll student when a bundle associated with a course is canceled.

= 1.7 =
* Fix: Issue where expired bundle shared a course with an active membership level removing access to a course.

= 1.6 =
* Fix: Issue where active bundles shared a course with an inactive bundle removing access to a course.

= 1.5 =
* Fix: Issue where membership bundle status were not evaluated properly resulting in students not being able to access course units from course outline.

= 1.4 =
* New: Added functionality to prevent access to a course when a membership level or a bundle is NOT active. Note, this does not de-enroll the student, it merely prevents them from viewing course content, hence the course will be visible on the course progress page, however, units will not be "clickable" nor accessible.

= 1.3 =
* Fix: Fixed bug where levels ID's that were double digit were not being processed correctly when retroactively assigning courses

= 1.2 =
* Fix: Fixed bug in which retroactive enrollment was not working for courses associated with a membership level

= 1.1 =
* New: Added the ability to retroactively enroll students to a course when adding a new course to an existing product.
* New: Added automatic enrollment support for both bundles and membership levels and bundles.

= 1.0 =
* Initial release


== Upgrade notice ==