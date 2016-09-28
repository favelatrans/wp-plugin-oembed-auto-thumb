# Wordpress Plugin - OEmbed Auto-Thumbs

Set thumbnail of (o)embedded content as featured image in the current post.


## Usage / Installation
Add to your sites `composer.json`:

```json
{
    "repositories": [
        {
          "type": "vcs",
          "url": "git@github.com:snrbrnjna/wp-plugin-oembed-auto-thumbs.git"
        }
     ],
    "require": {
        "snrbrnjna/wp-plugin-oembed-auto-thumbs": "dev-master"
    }
}
```

After installation with `composer update` you need to activate the plugin and there you go, add a url to an oembed URL in your posts edit screen and wait for the featured image to be set within a few seconds.


## Develop
Build assets (js and sass) and watch for changes and build again...

```
$ npm run dev
```
