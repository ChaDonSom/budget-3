#!/bin/bash
# scripts/find-large-components.sh
# Identify Vue components that exceed the 150-line limit

echo "🔍 Finding Vue files exceeding 150 lines..."
echo "=========================================="

find src -name "*.vue" -exec wc -l {} + | sort -nr | while read line count file; do
    if [ "$count" -gt 150 ] 2>/dev/null; then
        echo "📄 $file: $count lines ($(($count - 150)) over limit)"
    fi
done

echo ""
echo "📊 Summary:"
total_files=$(find src -name "*.vue" | wc -l)
large_files=$(find src -name "*.vue" -exec wc -l {} + | awk '$1 > 150' | wc -l)

echo "Total Vue files: $total_files"
echo "Files over 150 lines: $large_files"

if [ "$large_files" -gt 0 ]; then
    echo "⚠️  $large_files files need to be split"
    exit 1
else
    echo "✅ All files meet the 150-line limit"
    exit 0
fi