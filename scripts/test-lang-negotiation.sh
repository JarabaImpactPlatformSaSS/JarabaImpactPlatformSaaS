#!/bin/bash
# Test language negotiation from within Lando container
# Check HTML lang attribute and content-language header for /en vs /pt-br vs /

echo "=== Testing /en ==="
curl -sk https://localhost/en 2>/dev/null | grep -oP 'html[^>]*lang="[^"]*"' | head -1
curl -sk https://localhost/en 2>/dev/null | grep -oP 'Soluciones|Solutions' | head -3
echo "---"

echo "=== Testing /pt-br ==="
curl -sk https://localhost/pt-br 2>/dev/null | grep -oP 'html[^>]*lang="[^"]*"' | head -1
curl -sk https://localhost/pt-br 2>/dev/null | grep -oP 'Soluciones|Soluções' | head -3
echo "---"

echo "=== Testing / (default ES) ==="
curl -sk https://localhost/ 2>/dev/null | grep -oP 'html[^>]*lang="[^"]*"' | head -1
curl -sk https://localhost/ 2>/dev/null | grep -oP 'Soluciones' | head -3
echo "---"

echo "=== Content-Language headers ==="
echo "/ header:"
curl -skI https://localhost/ 2>/dev/null | grep -i "content-language"
echo "/en header:"
curl -skI https://localhost/en 2>/dev/null | grep -i "content-language"
echo "/pt-br header:"
curl -skI https://localhost/pt-br 2>/dev/null | grep -i "content-language"
