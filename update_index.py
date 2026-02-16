import sys

with open('index.html', 'r') as f:
    content = f.read()

search_str = """                    // Wysyłka do serwera
                    const response = await fetch('send_pdf.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.showNotification('Plik PDF został wysłany do biura!', 'success');
                    } else {
                        throw new Error(result.message || 'Błąd podczas wysyłania');
                    }"""

replace_str = """                    // Wysyłka do serwera
                    const response = await fetch('send_pdf.php', {
                        method: 'POST',
                        body: formData
                    });

                    const responseText = await response.text();
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (e) {
                        console.error('Błąd parsowania JSON. Otrzymano:', responseText);
                        const preview = responseText.length > 500 ? responseText.substring(0, 500) + '...' : responseText;
                        // Sprawdź czy to HTML (częsty błąd 404/500)
                        if (preview.trim().startsWith('<')) {
                             throw new Error('Serwer zwrócił stronę HTML zamiast JSON. Sprawdź konfigurację serwera (czy obsługuje PHP?). Treść: ' + preview);
                        }
                        throw new Error('Serwer zwrócił nieprawidłowe dane. Treść: ' + preview);
                    }

                    if (!response.ok) {
                        throw new Error('Błąd HTTP ' + response.status + ': ' + (result.message || response.statusText));
                    }

                    if (result.success) {
                        this.showNotification('Plik PDF został wysłany do biura!', 'success');
                    } else {
                        throw new Error(result.message || 'Błąd podczas wysyłania');
                    }"""

if search_str in content:
    new_content = content.replace(search_str, replace_str)
    with open('index.html', 'w') as f:
        f.write(new_content)
    print("Successfully updated index.html")
else:
    print("Could not find the search string in index.html")
    # Debug: print surrounding lines to see why match failed
    start_idx = content.find("const response = await fetch('send_pdf.php'")
    if start_idx != -1:
        print("Found fetch call, but block didn't match perfectly. Surrounding content:")
        print(content[start_idx:start_idx+500])
    else:
        print("Could not even find fetch call")
