# Imagez

## Debug

Run `bin/build` script to build containers and set up composer.

then you can run:

    bin/debug

## Usage

### Upload from URL

Use post and include secret token:

    POST http://localhost:8080/images/upload-url?url=https://www.pragmoon.cz/uploaded_images/thumb/laura-a-jeji-tygri.jpg&token=some-secure-value

### View original image

To get original image, only image name is needed:

    http://localhost:8080/images/original/test.png

### Get image health

To get image health JSON, only image name is needed:

    http://localhost:8080/images/health/test.png

### View resized image

To get resized image, you must provide image size and optionally other params

- width (required)
- height (required)
- type (required, desired resize type - fit, crop or scale)
- ext (optional, desired image extension - jpg, png, webp or gif)

#### Unsecured

This will work only if securityToken is not configured on the server:

    http://localhost:8080/images/resized/test.png?width=450&height=400&type=crop&ext=webp

#### Secured

To get resized image securely you will need a validation token that is created from params (ext is optional, depending
on if you used the param):

    {secret-token}-{original_image_name}-{width}-{height}-{type}(-{ext})

You will need to hash verification token (CRC32 hash in hex chars):

    hash=HEX(CRC32(secret-test.png-450-400-crop-webp))

Then you can then request an image from imagez server:

    http://localhost:8080/images/resized/test.png?width=450&height=400&type=crop&ext=webp&token={hash}

## Deployment

- delete CompiledContainer.php in cache directory
