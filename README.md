# Objective-C Markdown Document Generator
This is a  project developed to generate GitHub markdown document from Objective-C header files with HeaderDoc Tags for Longdo Map SDK for iOS.

The program is developed on PHP run via PHP command line.
```
php doc.php
```

In the file, you can set ```INPUT_DIR``` for input header files, the program will load all header files there, and ```OUTPUT_DIR``` where the generated markdown will be written to.

The project is in very early state, it does not complete and not support all header tags. I implemented this to release documents for [Longdo Map SDK for iOS](https://github.com/MetamediaTechnology/longdo-map-demo-ios/blob/master/README.md#longdo-map-sdk-reference). Please feel free to medify and request for a merge if you wish. :)
