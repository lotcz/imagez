# Imagez

## Debug

Run `bin/build` script to build containers and set up composer.

then you can run:

    bin/debug

## Usage

To get original image, only image name is needed:

    http://localhost:8080/images/original/test.png

To get resized image, you must provide image size and optionally other params

- width (required)
- height (required)
- type (required, desired resize type - fit, crop or scale)
- ext (optional, desired image extension - jpg, png, webp or gif)

### Unsecured

This will work only if securityToken is not configured on the server:

    http://localhost:8080/images/resized/test.png?width=450&height=400&type=crop&ext=webp

### Debug mode

To get resized image securely you will need a validation token that is created from params (ext is optional, depending
on if you used the param):

    {secret-token}-{original_image_name}-{width}h{height}-{type}(-{ext})

If the security token is `secret` then you can request image from production server:

    http://localhost:8080/images/resized/test.png?width=450&height=400&type=crop&ext=webp&token=secret-test.png-450-400-crop-webp

### Production

You will need to hash verification token first:

    hash=HASH(secret-test.png-450-400-crop-webp)

Then you can request an image from production server:

    http://localhost:8080/images/resized/test.png?width=450&height=400&type=crop&ext=webp&token={hash}
