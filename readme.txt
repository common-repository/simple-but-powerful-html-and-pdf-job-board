=== Simple but Powerful HTML and PDF Job Board===
Contributors: michaelni
Tags: Job Board, PDF, HTML, Jobs, Advertisment
Requires at least: 3.4
Tested up to: 4.4.1
Stable tag: 0.9
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

SPJB allows users to quickly create job offers as HTML page and PDF file using the WordPress WYSIWYG editor. Templates are supported to significantly speed up the recruitment process.

== Description ==

SPJB allows users to quickly create job offers as HTML page and PDF file using the WordPress WYSIWYG (What You See Is What You Get) editor. 

= Job templates =
With the ability to create templates for future job offers, the recruitment process receives a significant speed-up. There is no need to first open Word or other programs, load a template, save it as PDF, then upload it to WordPress and link it on the website - SPJB does it all automatically and within seconds.

= PDF templates =
Depending on your website and company, you may either create the template(s) using the usual WordPress editor or upload complex PDF templates exactly following your Corporate Identity.

= Shortcode and frontend display =
The simple [jobboard] shortcode allows you to create an unlimited amount of jobboards. It may be added to WordPress posts like any other shortcodes. You can select and specify the content and columns of the individual job board shown on the front-end by using shortcode attributes.

= Key features: =
* Automatic PDF and HTML generation
* Use the built-in WordPress WYSIWYG editor to create PDF files
* WordPress- or PDF-built templates to speed up your recruiting process
* Unlimited amount of jobs and templates
* Customizable Job Boards (inserted by shortcode) featuring the columns Job ID, Job Type (e.g. Full time), Title, HTML Link, PDF Link, Apply to Link
* Multilingual (frontend) out of the box due to plugin flexibility - no additional plugin required

= Important note =
Uploading complex PDF templates (template.pdf) and using them to create PDF job offers may require advanced customization (possibly PHP-file changes). This customization service is not included in the purchase. Standard PDF files including images and logos can however easily be created by using the WordPress WYSIWYG editor.

= Technical background for complex PDF templates =
You can simply upload an empty PDF file featuring your companies letter paper and corporate identity. The PDF generator will then translate the WordPress-Editor-generated HTML into the PDF file. Depending on the layout of your PDF template, you will need to change the border position where the PDF generator starts placing/inserting the content. 

= Notes =
Supported image containers for PDF generation: JPG/JPEG
Default font: Helvetica, may be changed in PHP file.
Used libraries: TCPDF (GPL), FPDI (MIT) 

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload 'plugin-name.php' to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. You may created, edit or delete jobs and templates via the "Jobs" menu item on the left of the WordPress Admin menu.

== Frequently Asked Questions ==

For an extensive FAQ, please visit the "Overview / Instructions" page the plugin adds to WordPress.

= My image does not appear in the PDF =
SPJB only supports JPG/JPEG images as of now.

= The plugin does not create any HTML/PDF files = 
Please ensure that the PHP process has write/read access to the wp-content/uploads-folder

== Screenshots ==

1. Use templates to improve and speed up your human resource process
2. Templates can be easily used on the "add new job" page
3. Manage or edit jobs
4. Easily include job boards in posts using shortcodes
5. Job boards will use the theme design
6. An example PDF file, generated on the fly

== Changelog ==

= 0.9 = 
* Various small bugfixes
* Release version

= 0.8 =
* improved menu and help page

= 0.7 = 
* included image to PDF functionality for PDF files

= 0.6 =
* Added advanced shortcode functionalities

= 0.5 =
* First version