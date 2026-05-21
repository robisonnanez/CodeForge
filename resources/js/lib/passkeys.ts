type AuthnCredentialDescriptor = {
    id: string;
    type: PublicKeyCredentialType;
    transports?: AuthenticatorTransport[];
};

type AuthnRequestOptions = {
    challenge: string;
    timeout?: number;
    rpId?: string;
    userVerification?: UserVerificationRequirement;
    allowCredentials?: AuthnCredentialDescriptor[];
};

type AuthnUser = {
    id: string;
    name: string;
    displayName: string;
};

type AuthnCreationOptions = {
    challenge: string;
    rp: { name: string; id?: string };
    user: AuthnUser;
    pubKeyCredParams: { type: PublicKeyCredentialType; alg: number }[];
    timeout?: number;
    attestation?: AttestationConveyancePreference;
    authenticatorSelection?: AuthenticatorSelectionCriteria;
    excludeCredentials?: AuthnCredentialDescriptor[];
};

const toBase64Url = (buffer: ArrayBuffer): string => {
    const bytes = new Uint8Array(buffer);
    let binary = '';

    bytes.forEach((b) => {
        binary += String.fromCharCode(b);
    });

    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
};

const fromBase64Url = (value: string): ArrayBuffer => {
    const base64 = value.replace(/-/g, '+').replace(/_/g, '/');
    const padded = base64 + '='.repeat((4 - (base64.length % 4)) % 4);
    const binary = atob(padded);
    const bytes = Uint8Array.from(binary, (c) => c.charCodeAt(0));

    return bytes.buffer;
};

export const webAuthnSupported = (): boolean => typeof window !== 'undefined' && !!window.PublicKeyCredential;

export const startPasskeyAuthentication = async (options: unknown) => {
    const publicKey = options as AuthnRequestOptions;

    const credential = (await navigator.credentials.get({
        publicKey: {
            ...publicKey,
            challenge: fromBase64Url(publicKey.challenge),
            allowCredentials: publicKey.allowCredentials?.map((credentialItem: AuthnCredentialDescriptor) => ({
                ...credentialItem,
                id: fromBase64Url(credentialItem.id),
            })),
        },
    })) as PublicKeyCredential;

    const response = credential.response as AuthenticatorAssertionResponse;

    return {
        id: credential.id,
        rawId: toBase64Url(credential.rawId),
        type: credential.type,
        response: {
            authenticatorData: toBase64Url(response.authenticatorData),
            clientDataJSON: toBase64Url(response.clientDataJSON),
            signature: toBase64Url(response.signature),
            userHandle: response.userHandle ? toBase64Url(response.userHandle) : null,
        },
    };
};

export const startPasskeyRegistration = async (options: unknown) => {
    const publicKey = options as AuthnCreationOptions;

    const credential = (await navigator.credentials.create({
        publicKey: {
            ...publicKey,
            challenge: fromBase64Url(publicKey.challenge),
            user: {
                ...publicKey.user,
                id: fromBase64Url(publicKey.user.id),
            },
            excludeCredentials: publicKey.excludeCredentials?.map((credentialItem: AuthnCredentialDescriptor) => ({
                ...credentialItem,
                id: fromBase64Url(credentialItem.id),
            })),
        },
    })) as PublicKeyCredential;

    const response = credential.response as AuthenticatorAttestationResponse;

    return {
        id: credential.id,
        rawId: toBase64Url(credential.rawId),
        type: credential.type,
        response: {
            attestationObject: toBase64Url(response.attestationObject),
            clientDataJSON: toBase64Url(response.clientDataJSON),
        },
    };
};
