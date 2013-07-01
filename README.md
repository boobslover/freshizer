Usage:

include 'freshizer.php';

// not fixed height = false parameter
```echo fImg::resize( 'http://domain.com/wp/image.jpg', 200, 200, false );```

// fixed height = true parameter
```echo fImg::resize( 'http://domain.com/wp/image.jpg', 200, 200, true );```

The last two (height and fixed) parameters are optional. This script goes with automated caching, so you will be not overloading your server. Also this script does not even connect to your database, yay :)
All images are stored in:

```
/wp-content/uploads/freshizer/
```