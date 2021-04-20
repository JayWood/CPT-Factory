# CPT Factory

A tool to make custom post type registration just a bit simpler. Automatically registers post type labels and messages, 
and provides helpful methods.

> If you're looking at this library for a single post type, **this is overkill**. Just register a single type using the
> core [register_post_type](https://developer.wordpress.org/reference/functions/register_post_type/) function.

### Disclaimer

This is a hard-fork from the original [CPT Core](https://github.com/WebDevStudios/CPT_Core) repository provided by 
[WebDevStudios](https://webdevstudios.com). Various updates have been made and given the likelyhood of getting this merged back in ( considering the
substantial amount of changes ) - a hard-fork may have been best. This is OSS after all, so feel free to merge or fork
as yous see fit. 

### TODO
* Unit tests w/ PEST

### Changes from CPT_Core by WebDevStudios
* PHPcs implementation
* Travis builds
* Easier to use with composer includes using PSR-4
* Update to PHP 7.3 minimum.
* Stricter i18n functions where appropriate.
* Use exceptions in place of wp_die
* Allow defaults to be overridden instead of hard-coding them.
* Set the new `type` property instead of overwriting CPT_Core::cpt_args
