<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Psr\Log\LoggerInterface;

class ValidEmailValidator extends ConstraintValidator
{
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!$constraint instanceof ValidEmail) {
            throw new UnexpectedTypeException($constraint, ValidEmail::class);
        }

        // Email format validation using PHP's filter_var
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
            return;
        }

        // Extract the domain part
        $domain = substr(strrchr($value, "@"), 1);

        // Check domain MX records to verify it's a potentially valid email domain
        if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
            if ($this->logger) {
                $this->logger->debug('Email domain DNS check failed', [
                    'email' => $value,
                    'domain' => $domain
                ]);
            }
            $this->context->buildViolation('This email domain does not appear to be valid.')
                ->setParameter('{{ value }}', $value)
                ->addViolation();
            return;
        }

        // Check if domain has a valid TLD
        $validTlds = [
            // Generic TLDs
            'com', 'org', 'net', 'edu', 'gov', 'mil', 'int', 'info', 'biz', 'name', 
            'pro', 'museum', 'aero', 'coop', 'jobs', 'travel', 'mobi', 'asia',
            'tel', 'post', 'xxx', 'io', 'co', 'me', 'tv', 'cc', 'ws', 'bz',
            'app', 'dev', 'blog', 'tech', 'online', 'store', 'site', 'website',
            
            // Country TLDs
            'uk', 'fr', 'de', 'jp', 'au', 'ca', 'tn', 'us', 'ru', 'ch', 'it', 
            'nl', 'se', 'no', 'es', 'dk', 'be', 'at', 'pt', 'pl', 'fi', 'gr',
            'ie', 'hu', 'cz', 'sk', 'ro', 'bg', 'hr', 'ee', 'lt', 'lv', 'cy',
            'lu', 'mt', 'si', 'dz', 'ma', 'tr', 'il', 'ae', 'qa', 'sa', 'eg',
            'za', 'kr', 'cn', 'in', 'br', 'mx', 'ar', 'cl', 'pe', 'co', 've',
            'nz', 'sg', 'my', 'th', 'id', 'ph', 'vn', 'ua', 'by', 'kz', 'uz',
            
            // New TLDs
            'academy', 'accountant', 'active', 'actor', 'agency', 'airforce',
            'apartments', 'army', 'associates', 'attorney', 'auction', 'audio',
            'band', 'bargains', 'beer', 'best', 'bid', 'bike', 'black', 'blue',
            'boutique', 'builders', 'business', 'buzz', 'cab', 'camera', 'camp',
            'capital', 'cards', 'care', 'careers', 'cash', 'catering', 'center',
            'ceo', 'charity', 'chat', 'cheap', 'christmas', 'church', 'city',
            'claims', 'cleaning', 'click', 'clinic', 'clothing', 'cloud', 'club',
            'coach', 'codes', 'coffee', 'community', 'company', 'computer',
            'condos', 'construction', 'consulting', 'contractors', 'cooking',
            'cool', 'country', 'coupons', 'credit', 'creditcard', 'cricket',
            'cruises', 'dance', 'date', 'dating', 'deals', 'degree', 'delivery',
            'democrat', 'dental', 'design', 'diamonds', 'digital', 'direct',
            'directory', 'discount', 'domains', 'education', 'email', 'energy',
            'engineer', 'engineering', 'enterprises', 'equipment', 'estate',
            'events', 'exchange', 'expert', 'exposed', 'express', 'fail', 'faith',
            'family', 'fans', 'farm', 'fashion', 'finance', 'financial', 'fish',
            'fishing', 'fit', 'fitness', 'flights', 'florist', 'flowers', 'football',
            'forex', 'forsale', 'foundation', 'fund', 'furniture', 'futbol', 'fyi',
            'gallery', 'games', 'garden', 'gent', 'gift', 'gifts', 'gives', 'glass',
            'global', 'gmbh', 'gold', 'golf', 'graphics', 'gratis', 'green', 'gripe',
            'group', 'guide', 'guitars', 'guru', 'haus', 'healthcare', 'help',
            'hiphop', 'hockey', 'holdings', 'holiday', 'horse', 'host', 'hosting',
            'house', 'how', 'immo', 'immobilien', 'industries', 'ink', 'institute',
            'insure', 'international', 'investments', 'jetzt', 'jewelry', 'juegos',
            'kaufen', 'kim', 'kitchen', 'kiwi', 'land', 'lawyer', 'lease', 'legal',
            'life', 'lighting', 'limited', 'limo', 'link', 'live', 'llc', 'loan',
            'loans', 'lol', 'love', 'ltd', 'luxury', 'maison', 'management', 'market',
            'marketing', 'mba', 'media', 'memorial', 'men', 'menu', 'miami', 'moda',
            'moe', 'mom', 'money', 'mortgage', 'navy', 'network', 'news', 'ninja',
            'one', 'ong', 'onl', 'ooo', 'organic', 'partners', 'parts', 'party',
            'pharmacy', 'photo', 'photography', 'photos', 'physio', 'pics', 'pictures',
            'pink', 'pizza', 'place', 'plumbing', 'plus', 'poker', 'press', 'productions',
            'promo', 'properties', 'property', 'pub', 'qpon', 'racing', 'radio',
            'recipes', 'red', 'rehab', 'reisen', 'rent', 'rentals', 'repair', 'report',
            'republican', 'rest', 'restaurant', 'review', 'reviews', 'rich', 'rip',
            'rocks', 'rodeo', 'run', 'sale', 'salon', 'sarl', 'school', 'schule',
            'science', 'scot', 'services', 'sexy', 'shiksha', 'shoes', 'shop', 'shopping',
            'show', 'singles', 'ski', 'soccer', 'social', 'software', 'solar', 'solutions',
            'soy', 'space', 'sports', 'srl', 'studio', 'style', 'sucks', 'supplies',
            'supply', 'support', 'surf', 'surgery', 'systems', 'tattoo', 'tax', 'taxi',
            'team', 'tennis', 'theater', 'theatre', 'tickets', 'tienda', 'tips', 'tires',
            'today', 'tools', 'top', 'tours', 'town', 'toys', 'trade', 'trading',
            'training', 'tube', 'university', 'uno', 'vacations', 'vegas', 'ventures',
            'vet', 'viajes', 'video', 'villas', 'vin', 'vip', 'vision', 'vodka', 'vote',
            'voting', 'voyage', 'watch', 'webcam', 'wedding', 'wiki', 'win', 'wine',
            'work', 'works', 'world', 'wtf', 'xyz', 'yachts', 'yoga', 'zone', 'rich'
            
            // You can add more TLDs as needed
        ];

        $domainParts = explode('.', $domain);
        $tld = strtolower(end($domainParts));

        if (!in_array($tld, $validTlds)) {
            if ($this->logger) {
                $this->logger->debug('Email TLD validation failed', [
                    'email' => $value,
                    'tld' => $tld
                ]);
            }
            $this->context->buildViolation($constraint->invalidDomainMessage)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}