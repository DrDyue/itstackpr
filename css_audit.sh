#!/bin/bash

# Iegūt visas CSS klases no app.css
echo "CSS klases no app.css:"
grep -oP '^\.([\w-]+)(?:\s|{|,|:)' resources/css/app.css | sed 's/^\.//;s/[^a-zA-Z0-9_-].*$//' | sort | uniq > /tmp/css_classes.txt
echo "Kopā: $(wc -l < /tmp/css_classes.txt) klases"

# Skannēt visas blade templates, lai redzētu, kuras klases tiek izmantotas
echo ""
echo "Meklējot lietotās klases blade failos..."
grep -rh 'class=' resources/views/ | grep -oP 'class=["\047]([^"'\'']*)["\047]' | sed 's/class=["\047]//;s/["\047]$//' | tr ' ' '\n' | sort | uniq > /tmp/used_classes.txt
echo "Kopā meklētas: $(wc -l < /tmp/used_classes.txt) unikālas klases"

# Salīdzināt
echo ""
echo "CSS klases, kas NEIZSKATĀS tikt izmantotas:"
comm -23 /tmp/css_classes.txt /tmp/used_classes.txt | head -20

