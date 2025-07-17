#!/bin/bash
# scripts/check-folder-structure.sh
# Check if folders follow the 3+ files minimum rule

echo "🔍 Checking folder structure against 3+ files rule..."
echo "=================================================="

violation_count=0

find src -type d | while read dir; do
    vue_count=$(find "$dir" -maxdepth 1 -name "*.vue" | wc -l)
    
    if [ "$vue_count" -gt 0 ] && [ "$vue_count" -lt 3 ]; then
        echo "⚠️  $dir: Only $vue_count Vue file(s) - consider consolidating"
        ((violation_count++))
    elif [ "$vue_count" -ge 3 ]; then
        echo "✅ $dir: $vue_count Vue files"
    fi
done

if [ "$violation_count" -gt 0 ]; then
    echo ""
    echo "📋 Consolidation suggestions:"
    echo "- Move single files to more general folders"
    echo "- Combine related single-file folders"
    echo "- Only create new folders when you have 3+ related files"
fi