Usage
-----


// include freshizer in your php project
```
include 'freshizer.php';
```

// not fixed height = false parameter
```
echo fImg::resize( 'http://domain.com/wp/image.jpg', 200, 200, false );
```

// fixed height = true parameter
```
echo fImg::resize( 'http://domain.com/wp/image.jpg', 200, 200, true );
```

The last two (height and fixed) parameters are optional. This script goes with automated caching, so you will be not overloading your server. Also this script does not even connect to your database, yay :)
All images are stored in:

```
/wp-content/uploads/freshizer/
```

Retina Support
--------------

Freshizer now automatically creates retina version of each image. This retina image has @2x suffix which makes it really easy to implement Retina support with scripts such as retina.js. If the retina.js script detects there is a @2x variant of the same image on your server, it will automatically replace normal image with it's retina variant.

https://github.com/imulus/retinajs

Example:

You upload 1500x1500 photograph and resize it like this to 500x500

```
echo fImg::resize( 'http://domain.com/wp/photo.jpg', 500, 500, false );
```

This will create 2 files, one for normal DPI displays and one for Retina displays:

```
photo.jpg - 500x500
photo@2x.jpg - 1000x1000

```

Example #2:

You upload 300x300 photograph and resize it like this to 200x200

```
echo fImg::resize( 'http://domain.com/wp/photo.jpg', 200, 200, false );
```

This will create 2 files, one for normal DPI displays and one for Retina displays:

```
photo.jpg - 200x200
photo@2x.jpg - 400x400 - yes, this image will be slightly blurry but that is the tax for ease of use compared to other solutions where you have to upload normal and retina images seperately

```