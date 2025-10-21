<?php

namespace App\Entity;

use App\Repository\ContractConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractConfigRepository::class)]
#[ORM\Table(name: 'contract_config')]
#[ORM\UniqueConstraint(name: 'UNIQ_CONTRACT_CONFIG_ORG_COMP', columns: ['organization_id', 'company_id'])]
class ContractConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    // Apparence
    #[ORM\Column(length: 10)]
    private string $contractLanguage = 'fr';

    #[ORM\Column(length: 255)]
    private string $contractTitle = 'Contrat de Bail';

    #[ORM\Column(length: 255)]
    private string $contractMainTitle = 'CONTRAT DE BAIL D\'HABITATION';

    #[ORM\Column(length: 255)]
    private string $contractFontFamily = 'DejaVu Sans, sans-serif';

    #[ORM\Column(length: 10)]
    private string $contractFontSize = '11pt';

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 1)]
    private string $contractLineHeight = '1.6';

    #[ORM\Column(length: 7)]
    private string $contractTextColor = '#333';

    #[ORM\Column(length: 10)]
    private string $contractMargin = '40px';

    #[ORM\Column(length: 10)]
    private string $contractTitleSize = '24pt';

    #[ORM\Column(length: 10)]
    private string $contractLabelWidth = '180px';

    // Couleurs
    #[ORM\Column(length: 7)]
    private string $contractPrimaryColor = '#0066cc';

    #[ORM\Column(length: 7)]
    private string $contractInfoBgColor = '#f5f5f5';

    #[ORM\Column(length: 7)]
    private string $contractHighlightColor = '#f0f8ff';

    // Entreprise
    #[ORM\Column(length: 255)]
    private string $contractCompanyName = 'LOKAPRO Gestion';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contractCompanyAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contractLogoUrl = null;

    // Titres des sections
    #[ORM\Column(length: 255)]
    private string $contractSection1Title = 'ARTICLE 1 : LES PARTIES';

    #[ORM\Column(length: 255)]
    private string $contractSection2Title = 'ARTICLE 2 : DÉSIGNATION DU BIEN LOUÉ';

    #[ORM\Column(length: 255)]
    private string $contractSection3Title = 'ARTICLE 3 : DURÉE DU BAIL';

    #[ORM\Column(length: 255)]
    private string $contractSection4Title = 'ARTICLE 4 : LOYER ET CHARGES';

    #[ORM\Column(length: 255)]
    private string $contractSection5Title = 'ARTICLE 5 : DÉPÔT DE GARANTIE';

    #[ORM\Column(length: 255)]
    private string $contractSection6Title = 'ARTICLE 6 : OBLIGATIONS DU LOCATAIRE';

    #[ORM\Column(length: 255)]
    private string $contractSection7Title = 'ARTICLE 7 : OBLIGATIONS DU BAILLEUR';

    #[ORM\Column(length: 255)]
    private string $contractSection8Title = 'ARTICLE 8 : CLAUSE RÉSOLUTOIRE';

    // Titres des parties
    #[ORM\Column(length: 255)]
    private string $contractLandlordTitle = 'LE BAILLEUR';

    #[ORM\Column(length: 255)]
    private string $contractTenantTitle = 'LE LOCATAIRE';

    // Signatures
    #[ORM\Column(length: 255)]
    private string $contractSignatureLandlordTitle = 'Le Bailleur';

    #[ORM\Column(length: 255)]
    private string $contractSignatureTenantTitle = 'Le Locataire';

    #[ORM\Column(length: 255)]
    private string $contractSignaturePlace = 'Fait à ____________';

    #[ORM\Column(length: 255)]
    private string $contractSignatureLandlordText = 'Signature';

    #[ORM\Column(length: 255)]
    private string $contractSignatureTenantText = 'Signature précédée de la mention "Lu et approuvé"';

    // Footer
    #[ORM\Column(length: 255)]
    private string $contractFooterText = 'Document généré le';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getContractLanguage(): string
    {
        return $this->contractLanguage;
    }

    public function setContractLanguage(string $contractLanguage): static
    {
        $this->contractLanguage = $contractLanguage;
        return $this;
    }

    public function getContractTitle(): string
    {
        return $this->contractTitle;
    }

    public function setContractTitle(string $contractTitle): static
    {
        $this->contractTitle = $contractTitle;
        return $this;
    }

    public function getContractMainTitle(): string
    {
        return $this->contractMainTitle;
    }

    public function setContractMainTitle(string $contractMainTitle): static
    {
        $this->contractMainTitle = $contractMainTitle;
        return $this;
    }

    public function getContractFontFamily(): string
    {
        return $this->contractFontFamily;
    }

    public function setContractFontFamily(string $contractFontFamily): static
    {
        $this->contractFontFamily = $contractFontFamily;
        return $this;
    }

    public function getContractFontSize(): string
    {
        return $this->contractFontSize;
    }

    public function setContractFontSize(string $contractFontSize): static
    {
        $this->contractFontSize = $contractFontSize;
        return $this;
    }

    public function getContractLineHeight(): string
    {
        return $this->contractLineHeight;
    }

    public function setContractLineHeight(string $contractLineHeight): static
    {
        $this->contractLineHeight = $contractLineHeight;
        return $this;
    }

    public function getContractTextColor(): string
    {
        return $this->contractTextColor;
    }

    public function setContractTextColor(string $contractTextColor): static
    {
        $this->contractTextColor = $contractTextColor;
        return $this;
    }

    public function getContractMargin(): string
    {
        return $this->contractMargin;
    }

    public function setContractMargin(string $contractMargin): static
    {
        $this->contractMargin = $contractMargin;
        return $this;
    }

    public function getContractTitleSize(): string
    {
        return $this->contractTitleSize;
    }

    public function setContractTitleSize(string $contractTitleSize): static
    {
        $this->contractTitleSize = $contractTitleSize;
        return $this;
    }

    public function getContractLabelWidth(): string
    {
        return $this->contractLabelWidth;
    }

    public function setContractLabelWidth(string $contractLabelWidth): static
    {
        $this->contractLabelWidth = $contractLabelWidth;
        return $this;
    }

    public function getContractPrimaryColor(): string
    {
        return $this->contractPrimaryColor;
    }

    public function setContractPrimaryColor(string $contractPrimaryColor): static
    {
        $this->contractPrimaryColor = $contractPrimaryColor;
        return $this;
    }

    public function getContractInfoBgColor(): string
    {
        return $this->contractInfoBgColor;
    }

    public function setContractInfoBgColor(string $contractInfoBgColor): static
    {
        $this->contractInfoBgColor = $contractInfoBgColor;
        return $this;
    }

    public function getContractHighlightColor(): string
    {
        return $this->contractHighlightColor;
    }

    public function setContractHighlightColor(string $contractHighlightColor): static
    {
        $this->contractHighlightColor = $contractHighlightColor;
        return $this;
    }

    public function getContractCompanyName(): string
    {
        return $this->contractCompanyName;
    }

    public function setContractCompanyName(string $contractCompanyName): static
    {
        $this->contractCompanyName = $contractCompanyName;
        return $this;
    }

    public function getContractCompanyAddress(): ?string
    {
        return $this->contractCompanyAddress;
    }

    public function setContractCompanyAddress(?string $contractCompanyAddress): static
    {
        $this->contractCompanyAddress = $contractCompanyAddress;
        return $this;
    }

    public function getContractLogoUrl(): ?string
    {
        return $this->contractLogoUrl;
    }

    public function setContractLogoUrl(?string $contractLogoUrl): static
    {
        $this->contractLogoUrl = $contractLogoUrl;
        return $this;
    }

    public function getContractSection1Title(): string
    {
        return $this->contractSection1Title;
    }

    public function setContractSection1Title(string $contractSection1Title): static
    {
        $this->contractSection1Title = $contractSection1Title;
        return $this;
    }

    public function getContractSection2Title(): string
    {
        return $this->contractSection2Title;
    }

    public function setContractSection2Title(string $contractSection2Title): static
    {
        $this->contractSection2Title = $contractSection2Title;
        return $this;
    }

    public function getContractSection3Title(): string
    {
        return $this->contractSection3Title;
    }

    public function setContractSection3Title(string $contractSection3Title): static
    {
        $this->contractSection3Title = $contractSection3Title;
        return $this;
    }

    public function getContractSection4Title(): string
    {
        return $this->contractSection4Title;
    }

    public function setContractSection4Title(string $contractSection4Title): static
    {
        $this->contractSection4Title = $contractSection4Title;
        return $this;
    }

    public function getContractSection5Title(): string
    {
        return $this->contractSection5Title;
    }

    public function setContractSection5Title(string $contractSection5Title): static
    {
        $this->contractSection5Title = $contractSection5Title;
        return $this;
    }

    public function getContractSection6Title(): string
    {
        return $this->contractSection6Title;
    }

    public function setContractSection6Title(string $contractSection6Title): static
    {
        $this->contractSection6Title = $contractSection6Title;
        return $this;
    }

    public function getContractSection7Title(): string
    {
        return $this->contractSection7Title;
    }

    public function setContractSection7Title(string $contractSection7Title): static
    {
        $this->contractSection7Title = $contractSection7Title;
        return $this;
    }

    public function getContractSection8Title(): string
    {
        return $this->contractSection8Title;
    }

    public function setContractSection8Title(string $contractSection8Title): static
    {
        $this->contractSection8Title = $contractSection8Title;
        return $this;
    }

    public function getContractLandlordTitle(): string
    {
        return $this->contractLandlordTitle;
    }

    public function setContractLandlordTitle(string $contractLandlordTitle): static
    {
        $this->contractLandlordTitle = $contractLandlordTitle;
        return $this;
    }

    public function getContractTenantTitle(): string
    {
        return $this->contractTenantTitle;
    }

    public function setContractTenantTitle(string $contractTenantTitle): static
    {
        $this->contractTenantTitle = $contractTenantTitle;
        return $this;
    }

    public function getContractSignatureLandlordTitle(): string
    {
        return $this->contractSignatureLandlordTitle;
    }

    public function setContractSignatureLandlordTitle(string $contractSignatureLandlordTitle): static
    {
        $this->contractSignatureLandlordTitle = $contractSignatureLandlordTitle;
        return $this;
    }

    public function getContractSignatureTenantTitle(): string
    {
        return $this->contractSignatureTenantTitle;
    }

    public function setContractSignatureTenantTitle(string $contractSignatureTenantTitle): static
    {
        $this->contractSignatureTenantTitle = $contractSignatureTenantTitle;
        return $this;
    }

    public function getContractSignaturePlace(): string
    {
        return $this->contractSignaturePlace;
    }

    public function setContractSignaturePlace(string $contractSignaturePlace): static
    {
        $this->contractSignaturePlace = $contractSignaturePlace;
        return $this;
    }

    public function getContractSignatureLandlordText(): string
    {
        return $this->contractSignatureLandlordText;
    }

    public function setContractSignatureLandlordText(string $contractSignatureLandlordText): static
    {
        $this->contractSignatureLandlordText = $contractSignatureLandlordText;
        return $this;
    }

    public function getContractSignatureTenantText(): string
    {
        return $this->contractSignatureTenantText;
    }

    public function setContractSignatureTenantText(string $contractSignatureTenantText): static
    {
        $this->contractSignatureTenantText = $contractSignatureTenantText;
        return $this;
    }

    public function getContractFooterText(): string
    {
        return $this->contractFooterText;
    }

    public function setContractFooterText(string $contractFooterText): static
    {
        $this->contractFooterText = $contractFooterText;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Convertit l'entité en tableau pour compatibilité avec l'ancien système
     */
    public function toArray(): array
    {
        return [
            'contract_language' => $this->contractLanguage,
            'contract_title' => $this->contractTitle,
            'contract_main_title' => $this->contractMainTitle,
            'contract_font_family' => $this->contractFontFamily,
            'contract_font_size' => $this->contractFontSize,
            'contract_line_height' => $this->contractLineHeight,
            'contract_text_color' => $this->contractTextColor,
            'contract_margin' => $this->contractMargin,
            'contract_title_size' => $this->contractTitleSize,
            'contract_label_width' => $this->contractLabelWidth,
            'contract_primary_color' => $this->contractPrimaryColor,
            'contract_info_bg_color' => $this->contractInfoBgColor,
            'contract_highlight_color' => $this->contractHighlightColor,
            'contract_company_name' => $this->contractCompanyName,
            'contract_company_address' => $this->contractCompanyAddress,
            'contract_logo_url' => $this->contractLogoUrl,
            'contract_section_1_title' => $this->contractSection1Title,
            'contract_section_2_title' => $this->contractSection2Title,
            'contract_section_3_title' => $this->contractSection3Title,
            'contract_section_4_title' => $this->contractSection4Title,
            'contract_section_5_title' => $this->contractSection5Title,
            'contract_section_6_title' => $this->contractSection6Title,
            'contract_section_7_title' => $this->contractSection7Title,
            'contract_section_8_title' => $this->contractSection8Title,
            'contract_landlord_title' => $this->contractLandlordTitle,
            'contract_tenant_title' => $this->contractTenantTitle,
            'contract_signature_landlord_title' => $this->contractSignatureLandlordTitle,
            'contract_signature_tenant_title' => $this->contractSignatureTenantTitle,
            'contract_signature_place' => $this->contractSignaturePlace,
            'contract_signature_landlord_text' => $this->contractSignatureLandlordText,
            'contract_signature_tenant_text' => $this->contractSignatureTenantText,
            'contract_footer_text' => $this->contractFooterText,
        ];
    }

    /**
     * Met à jour l'entité depuis un tableau
     */
    public function fromArray(array $data): static
    {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        $this->updatedAt = new \DateTime();
        return $this;
    }
}
