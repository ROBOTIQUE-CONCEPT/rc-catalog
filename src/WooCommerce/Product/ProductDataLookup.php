<?php

declare(strict_types=1);

namespace WPRC\Catalog\WooCommerce\Product;

use WPRC\Core\Contracts\ERP\AddressProviderInterface;
use WPRC\Core\Contracts\ERP\CompanyProviderInterface;
use WPRC\Core\Contracts\ERP\ProductProviderInterface;
use WPRC\Core\Data\ERP\AddressData;
use WPRC\Core\Data\ERP\CompanyData;
use WPRC\Core\Data\ERP\ProductData;
use WPRC\Core\ERP\ProviderRegistry;

defined('ABSPATH') || exit;

/**
 * Catalog-facing ERP lookup facade.
 *
 * Catalog never talks to a concrete ERP connector. Reads are explicit admin
 * lookups performed through authenticated admin AJAX endpoints. No ERP read is
 * triggered by rendering or saving a WordPress object.
 */
final class ProductDataLookup
{
    public function __construct(private readonly ProviderRegistry $erp)
    {
    }

    public function activeErpSource(): string
    {
        return $this->erp->activeSource();
    }

    /** @return array<int,array{id:string,text:string,meta:array<string,mixed>}> */
    public function searchErpProducts(string $term): array
    {
        /** @var ProductProviderInterface $provider */
        $provider = $this->erp->get(ProductProviderInterface::class);
        return array_map(fn (ProductData $product): array => [
            'id' => $product->externalId,
            'text' => $product->name,
            'meta' => $this->productArray($product),
        ], $provider->search($term, 50));
    }

    /** @return array<int,array{id:string,text:string,meta:array<string,mixed>}> */
    public function searchCompanies(string $term): array
    {
        /** @var CompanyProviderInterface $provider */
        $provider = $this->erp->get(CompanyProviderInterface::class);
        return array_map(fn (CompanyData $company): array => [
            'id' => $company->externalId,
            'text' => $company->name,
            'meta' => $this->companyArray($company),
        ], $provider->search($term, 50));
    }

    /** @return array<int,array{id:string,text:string,meta:array<string,mixed>}> */
    public function searchAddresses(string $companyId): array
    {
        $id = trim($companyId);
        if ($id === '') {
            return [];
        }

        /** @var AddressProviderInterface $provider */
        $provider = $this->erp->get(AddressProviderInterface::class);
        $results = [];
        foreach ($provider->forCompany($id) as $address) {
            $meta = $this->addressArray($address);
            $results[] = [
                'id' => $address->externalId,
                'text' => $address->displayLabel(),
                'meta' => $meta,
            ];
        }
        return $results;
    }

    /** @return array<string,mixed> */
    private function productArray(ProductData $product): array
    {
        return [
            'id' => $product->externalId,
            'reference' => $product->reference,
            'sku' => $product->sku,
            'supplier_sku' => $product->supplierReference,
            'name' => $product->name,
            'brand' => $product->brand,
            'designation' => $product->designation,
            'category' => $product->category,
            'tariff_code' => $product->tariffCode,
            'country_of_origin' => $product->countryOfOrigin,
            'price' => $product->price,
            'weight' => $product->weight,
            'stock' => $product->stock,
            'image' => $product->imageUrl,
            'desc_fr' => $product->descriptionFr,
            'desc_en' => $product->descriptionEn,
            'disabled' => $product->disabled,
        ];
    }

    /** @return array<string,mixed> */
    private function companyArray(CompanyData $company): array
    {
        return [
            'id' => $company->externalId,
            'name' => $company->name,
            'currency' => $company->currency,
            'is_customer' => $company->isCustomer,
            'is_prospect' => $company->isProspect,
            'email' => $company->email,
            'phone' => $company->phone,
            'street' => $company->street,
            'postal_code' => $company->postalCode,
            'city' => $company->city,
            'country' => $company->country,
        ];
    }

    /** @return array<string,mixed> */
    private function addressArray(AddressData $address): array
    {
        return [
            'id' => $address->externalId,
            'company_id' => $address->companyExternalId,
            'label' => $address->displayLabel(),
            'street' => $address->street,
            'zip_code' => $address->postalCode,
            'city' => $address->city,
            'country' => $address->country,
            'country_code' => $address->countryCode,
        ];
    }
}
