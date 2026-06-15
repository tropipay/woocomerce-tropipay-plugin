import os
from PIL import Image, ImageDraw, ImageFont

def generate_assets():
    print("Starting assets generation...")
    
    # Paths
    logo_path = "/home/dani/.gemini/antigravity-ide/brain/8b6106fc-d392-48c9-a709-6d352c78c0c6/media__1781510518800.png"
    screenshot_path = "/home/dani/.gemini/antigravity-ide/brain/8b6106fc-d392-48c9-a709-6d352c78c0c6/media__1781510437200.png"
    assets_dir = "/home/dani/Documentos/TropiPay/plugins/woocommerce/woocomerce-tropipay-plugin/svn-checkout/assets"
    
    if not os.path.exists(assets_dir):
        os.makedirs(assets_dir)
        
    # Open base images
    logo = Image.open(logo_path).convert("RGBA")
    screenshot = Image.open(screenshot_path).convert("RGBA")
    
    # --- 1. GENERATE ICONS ---
    print("Generating icons...")
    # Crop the icon symbol from the left part of the logo (0, 0, 40, 32)
    # The actual icon is 40x32. We will center it inside a square transparent box.
    icon_cropped = logo.crop((0, 0, 40, 32))
    
    # Generate icon-256x256.png
    icon_256 = Image.new("RGBA", (256, 256), (0, 0, 0, 0))
    # Resize cropped icon to 200x160 (maintaining aspect ratio 5:4)
    icon_resized_256 = icon_cropped.resize((200, 160), Image.Resampling.LANCZOS)
    # Paste centered: x = (256-200)/2 = 28, y = (256-160)/2 = 48
    icon_256.paste(icon_resized_256, (28, 48), icon_resized_256)
    icon_256.save(os.path.join(assets_dir, "icon-256x256.png"), "PNG")
    
    # Generate icon-128x128.png
    icon_128 = icon_256.resize((128, 128), Image.Resampling.LANCZOS)
    icon_128.save(os.path.join(assets_dir, "icon-128x128.png"), "PNG")
    print("Icons generated successfully.")
    
    # --- 2. GENERATE BANNERS ---
    print("Generating banners...")
    # Create the high-res 1544x500 banner canvas
    banner_1544 = Image.new("RGBA", (1544, 500))
    
    # Fill with a beautiful dark gradient: #0b0e14 -> #1b263b
    for x in range(1544):
        factor = x / 1544.0
        r = int(11 + factor * (27 - 11))
        g = int(14 + factor * (38 - 14))
        b = int(20 + factor * (59 - 20))
        for y in range(500):
            banner_1544.putpixel((x, y), (r, g, b, 255))
            
    # Process Logo: Change the dark navy color (27, 38, 59) to white
    logo_white = logo.copy()
    for x in range(logo_white.width):
        for y in range(logo_white.height):
            r, g, b, a = logo_white.getpixel((x, y))
            if a > 30:
                logo_white.putpixel((x, y), (255, 255, 255, a))
                
    # Resize the white logo to make it prominent (3.5x scale: 120x32 -> 420x112)
    logo_resized = logo_white.resize((420, 112), Image.Resampling.LANCZOS)
    # Paste logo on the left side: x=100, y=160
    banner_1544.paste(logo_resized, (100, 160), logo_resized)
    
    # Add text "Gateway for WooCommerce"
    draw = ImageDraw.Draw(banner_1544)
    font_path = "/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf"
    
    # Fallback to default if font doesn't exist
    if os.path.exists(font_path):
        font = ImageFont.truetype(font_path, 36)
    else:
        font = ImageFont.load_default()
        
    # Text color: a modern soft indigo/blue #a5b4fc: (165, 180, 252)
    draw.text((100, 290), "Gateway for WooCommerce", fill=(165, 180, 252, 255), font=font)
    
    # Process screenshot: Resize and give it rounded corners
    # Scale screenshot to fit nicely: height = 400, width = 731 (original aspect ratio 1024/560 = 1.828)
    screenshot_resized = screenshot.resize((731, 400), Image.Resampling.LANCZOS)
    
    # Create rounded corners mask
    mask = Image.new("L", (731, 400), 0)
    mask_draw = ImageDraw.Draw(mask)
    mask_draw.rounded_rectangle((0, 0, 731, 400), radius=20, fill=255)
    
    # Paste screenshot on the right side: x=750, y=50
    banner_1544.paste(screenshot_resized, (750, 50), mask)
    
    # Save banner-1544x500.png
    banner_1544.save(os.path.join(assets_dir, "banner-1544x500.png"), "PNG")
    
    # Generate banner-772x250.png
    banner_772 = banner_1544.resize((772, 250), Image.Resampling.LANCZOS)
    banner_772.save(os.path.join(assets_dir, "banner-772x250.png"), "PNG")
    print("Banners generated successfully.")
    
    # --- 3. COPY SCREENSHOTS ---
    print("Copying screenshots...")
    # Save the original screenshot as screenshot-1.png
    screenshot.save(os.path.join(assets_dir, "screenshot-1.png"), "PNG")
    print("Screenshot copied successfully.")
    
    print("=== All assets generated successfully in svn-checkout/assets! ===")

if __name__ == "__main__":
    generate_assets()
