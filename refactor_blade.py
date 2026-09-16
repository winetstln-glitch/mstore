import os
import re

views_dir = "resources/views"
exclude_dirs = ["components", "layouts", "auth", "vendor", "errors", "users", "roles"]

def process_file(path):
    with open(path, "r", encoding="utf-8") as f:
        content = f.read()

    original_content = content

    # 1. Fix pagination: {{ $var->links() }} -> {{ $var->appends(request()->query())->links() }}
    content = re.sub(r"(\{\{\s*\$[a-zA-Z0-9_]+)->links\(\)\s*\}\}", r"\1->appends(request()->query())->links() }}", content)
    content = re.sub(r"(\{!!\s*\$[a-zA-Z0-9_]+)->links\(\)\s*!!\}", r"\1->appends(request()->query())->links() !!}", content)

    # 2. Inject <x-table-toolbar />
    # We look for <form ... name="search" ... </form> and replace it with <x-table-toolbar />
    # If not found, look for <div class="table-responsive"> or <table and inject before it.
    if "<table" in content and "x-table-toolbar" not in content:
        # Check if there is a search form
        search_form_pattern = re.compile(r"<form[^>]*>.*?(?:name=[\"']search[\"']|id=[\"']search[\"']).*?</form>", re.IGNORECASE | re.DOTALL)
        if search_form_pattern.search(content):
            content = search_form_pattern.sub(r"<div class=\"card-header bg-white py-3 border-bottom-0\">\n<x-table-toolbar />\n</div>", content)
        else:
            # Fallback: inject above <div class="table-responsive">
            table_resp_pattern = re.compile(r"<div[^>]*class=[\"'][^\"]*table-responsive[^\"]*[\"'][^>]*>")
            if table_resp_pattern.search(content):
                content = table_resp_pattern.sub(lambda m: "<div class=\"card-header bg-white py-3 border-bottom-0\">\n<x-table-toolbar />\n</div>\n" + m.group(0), content)
            else:
                # Fallback: inject above <table
                table_pattern = re.compile(r"<table[^>]*>")
                if table_pattern.search(content):
                    content = table_pattern.sub(lambda m: "<div class=\"card-header bg-white py-3 border-bottom-0\">\n<x-table-toolbar />\n</div>\n" + m.group(0), content)

    if content != original_content:
        with open(path, "w", encoding="utf-8") as f:
            f.write(content)
        print(f"Refactored: {path}")

for root, dirs, files in os.walk(views_dir):
    # filter exclude dirs
    dirs[:] = [d for d in dirs if d not in exclude_dirs]
    for file in files:
        if file.endswith(".blade.php"):
            process_file(os.path.join(root, file))
