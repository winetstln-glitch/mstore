import os
import re

views_dir = "resources/views"
exclude_dirs = ["components", "layouts", "auth", "vendor", "errors"]

def process_file(path):
    with open(path, "r", encoding="utf-8") as f:
        content = f.read()

    original_content = content

    # 1. Fix pagination: {{ $var->links() }} -> {{ $var->appends(request()->query())->links() }}
    content = re.sub(r"(\{\{\s*\$[a-zA-Z0-9_]+)->links\(\)\s*\}\}", r"\1->appends(request()->query())->links() }}", content)
    content = re.sub(r"(\{!!\s*\$[a-zA-Z0-9_]+)->links\(\)\s*!!\}", r"\1->appends(request()->query())->links() !!}", content)

    if content != original_content:
        with open(path, "w", encoding="utf-8") as f:
            f.write(content)
        print(f"Fixed Links: {path}")

for root, dirs, files in os.walk(views_dir):
    dirs[:] = [d for d in dirs if d not in exclude_dirs]
    for file in files:
        if file.endswith(".blade.php"):
            process_file(os.path.join(root, file))
