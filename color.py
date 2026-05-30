from PIL import Image
import sys

try:
    img = Image.open('images/twa-logo.png')
    img = img.convert('RGBA')
    colors = {}
    
    for x in range(0, img.width, 5):
        for y in range(0, img.height, 5):
            r, g, b, a = img.getpixel((x, y))
            if a > 100:
                if not (r > 240 and g > 240 and b > 240) and not (r < 15 and g < 15 and b < 15):
                    hex_val = "#{:02x}{:02x}{:02x}".format(r, g, b)
                    colors[hex_val] = colors.get(hex_val, 0) + 1
                    
    sorted_colors = sorted(colors.items(), key=lambda item: item[1], reverse=True)
    for k, v in sorted_colors[:5]:
        print(f"{k}: {v}")
except Exception as e:
    print(e)
