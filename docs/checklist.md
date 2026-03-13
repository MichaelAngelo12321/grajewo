# Status Projektu Info24

Ten dokument służy do śledzenia postępów prac nad portalem. Zawiera listę zrealizowanych funkcjonalności oraz zadań oczekujących na wykonanie lub weryfikację.

## 1. Panel Administracyjny (Backend)

Większość funkcjonalności panelu została zaimplementowana.

### Dashboard
- [x] Podgląd statystyk
- [x] Zarządzanie "Zdjęciem Dnia" i "Wideo Dnia" (akceptacja/usuwanie)
- [x] Zarządzanie cenami paliw (weryfikacja zgłoszeń użytkowników)

### Artykuły
- [x] Lista artykułów (z filtrowaniem i stronicowaniem)
- [x] Tworzenie/Edycja/Usuwanie artykułów
- [x] Zarządzanie komentarzami (ukrywanie/odblokowywanie)
- [x] Przypisywanie galerii do artykułów

### Galerie
- [x] Lista galerii
- [x] Tworzenie/Edycja/Usuwanie galerii (wgrywanie zdjęć)

### Kategorie Artykułów
- [x] Zarządzanie strukturą kategorii (drzewo)
- [x] Przenoszenie artykułów między kategoriami

### Użytkownicy
- [x] Lista użytkowników
- [x] Edycja danych i ról użytkowników
- [x] Raporty użytkowników ("Raport na gorąco") - ukrywanie/odblokowywanie

### Ceny Paliw (Stacje)
- [x] Lista stacji paliw
- [x] Dodawanie/Edycja/Usuwanie stacji
- [x] Aktualizacja cen paliw na stacjach

### Reklamy (Ogłoszenia Drobne)
- [x] Lista ogłoszeń (z podziałem na promowane/zwykłe)
- [x] Usuwanie ogłoszeń
- [x] Promowanie ogłoszeń (toggle promoted)
- [x] Zarządzanie kategoriami ogłoszeń (CRUD)

### Katalog Firm
- [x] Lista firm (z filtrowaniem i stronicowaniem)
- [x] Dodawanie/Edycja/Usuwanie firm
- [x] Zarządzanie kategoriami firm (CRUD)

### Strony Statyczne
- [x] Zarządzanie treścią stron (regulamin, kontakt, o nas itp.)

### Inne
- [x] Logowanie do panelu (SecurityController)

---

## 2. Baza Danych

Schemat bazy danych jest zaktualizowany i zgodny z kodem.

- [x] Migracje dla podstawowych encji (Article, User, Gallery, etc.)
- [x] Migracje dla Katalogu Firm (Company, CompanyCategory)
- [x] Migracje dla Ogłoszeń (Advertisement, AdvertisementCategory, dodanie kolumn `is_promoted`, `views`)

---

## 3. Frontend (Strona Publiczna)

Funkcjonalności dostępne dla użytkowników końcowych.

### Strona Główna
- [x] Wyświetlanie listy artykułów (wg kategorii)
- [x] Sekcje specjalne (pogoda, jakość powietrza, kursy walut - widgety)

### Artykuły
- [x] Widok listy artykułów (z podziałem na kategorie)
- [x] Widok szczegółów artykułu (treść, galeria, komentarze)
- [x] Kalendarz wydarzeń (lista wydarzeń na dany dzień)

### Ogłoszenia (Drobne)
- [x] Widok listy ogłoszeń (z filtrowaniem po kategorii)
- [x] Widok szczegółów ogłoszenia (nie dotyczy - ogłoszenia są zazwyczaj krótkie na liście, do weryfikacji czy jest osobny widok)
- [x] Formularz dodawania ogłoszenia przez użytkownika (`/ogloszenia/dodaj`)
- [x] Zabezpieczenie anty-spamowe (limit czasowy 2 minuty na IP)

### Katalog Firm
- [x] Widok listy firm (z filtrowaniem po kategorii)
- [x] Widok szczegółów firmy (opis, kontakt, mapa - do weryfikacji)
- [ ] Wyszukiwarka firm (sprawdzić czy jest zaimplementowana na frontendzie)

### Interakcje Użytkownika
- [x] Formularz przesyłania zdjęć (`/przeslij/zdjecie`)
- [x] Formularz przesyłania wideo (`/przeslij/film`)
- [x] Formularz zgłaszania cen paliw (`/przeslij/ceny-paliw`)
- [x] Formularz "Raport na gorąco" (`/raport/dodaj`)

---

## 4. Do Zrobienia / Weryfikacji (To-Do)

Lista zadań do wykonania w najbliższym czasie.

### Testy i Weryfikacja
- [ ] **Test Manualny (Ogłoszenia)**: Przejść ścieżkę: Dodanie ogłoszenia jako użytkownik -> Weryfikacja w panelu (czy widać) -> Edycja/Promowanie przez admina.
- [ ] **Test Manualny (Firmy)**: Przejść ścieżkę: Dodanie firmy w panelu -> Sprawdzenie widoczności na liście firm (frontend) -> Sprawdzenie szczegółów firmy.
- [ ] **Bezpieczeństwo**: Sprawdzić, czy trasy panelu (`/panel/*`) są dostępne tylko dla roli `ROLE_ADMIN` lub `ROLE_EDITOR`.

### Rozwój (Możliwe Usprawnienia)
- [ ] **Wyszukiwarka Globalna**: Czy istnieje wyszukiwarka przeszukująca artykuły, firmy i ogłoszenia jednocześnie?
- [ ] **SEO**: Sprawdzić generowanie meta tagów (title, description) dla podstron firm i ogłoszeń.
- [ ] **Powiadomienia**: Czy admin dostaje powiadomienie mailowe o nowym ogłoszeniu/raporcie? (Warto dodać jeśli nie ma).
- [ ] **Moderacja Komentarzy**: Czy jest system zgłaszania komentarzy przez użytkowników?

### Błędy (Bugfix)
- [ ] (Brak znanych błędów krytycznych na ten moment)

---

**Ostatnia aktualizacja:** 2026-02-23
