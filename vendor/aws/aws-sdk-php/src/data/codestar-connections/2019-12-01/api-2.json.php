<?php
// This file was auto-generated from sdk-root/src/data/codestar-connections/2019-12-01/api-2.json
return [ 'version' => '2.0', 'metadata' => [ 'apiVersion' => '2019-12-01', 'endpointPrefix' => 'codestar-connections', 'jsonVersion' => '1.0', 'protocol' => 'json', 'serviceFullName' => 'AWS CodeStar connections', 'serviceId' => 'CodeStar connections', 'signatureVersion' => 'v4', 'signingName' => 'codestar-connections', 'targetPrefix' => 'com.amazonaws.codestar.connections.CodeStar_connections_20191201', 'uid' => 'codestar-connections-2019-12-01', ], 'operations' => [ 'CreateConnection' => [ 'name' => 'CreateConnection', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'CreateConnectionInput', ], 'output' => [ 'shape' => 'CreateConnectionOutput', ], 'errors' => [ [ 'shape' => 'LimitExceededException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ResourceUnavailableException', ], ], ], 'CreateHost' => [ 'name' => 'CreateHost', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'CreateHostInput', ], 'output' => [ 'shape' => 'CreateHostOutput', ], 'errors' => [ [ 'shape' => 'LimitExceededException', ], ], ], 'CreateRepositoryLink' => [ 'name' => 'CreateRepositoryLink', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'CreateRepositoryLinkInput', ], 'output' => [ 'shape' => 'CreateRepositoryLinkOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ConcurrentModificationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'LimitExceededException', ], [ 'shape' => 'ResourceAlreadyExistsException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'CreateSyncConfiguration' => [ 'name' => 'CreateSyncConfiguration', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'CreateSyncConfigurationInput', ], 'output' => [ 'shape' => 'CreateSyncConfigurationOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ConcurrentModificationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'LimitExceededException', ], [ 'shape' => 'ResourceAlreadyExistsException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'DeleteConnection' => [ 'name' => 'DeleteConnection', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DeleteConnectionInput', ], 'output' => [ 'shape' => 'DeleteConnectionOutput', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], ], ], 'DeleteHost' => [ 'name' => 'DeleteHost', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DeleteHostInput', ], 'output' => [ 'shape' => 'DeleteHostOutput', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ResourceUnavailableException', ], ], ], 'DeleteRepositoryLink' => [ 'name' => 'DeleteRepositoryLink', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DeleteRepositoryLinkInput', ], 'output' => [ 'shape' => 'DeleteRepositoryLinkOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ConcurrentModificationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'SyncConfigurationStillExistsException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ThrottlingException', ], [ 'shape' => 'UnsupportedProviderTypeException', ], ], ], 'DeleteSyncConfiguration' => [ 'name' => 'DeleteSyncConfiguration', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'DeleteSyncConfigurationInput', ], 'output' => [ 'shape' => 'DeleteSyncConfigurationOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ConcurrentModificationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'LimitExceededException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'GetConnection' => [ 'name' => 'GetConnection', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'GetConnectionInput', ], 'output' => [ 'shape' => 'GetConnectionOutput', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ResourceUnavailableException', ], ], ], 'GetHost' => [ 'name' => 'GetHost', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'GetHostInput', ], 'output' => [ 'shape' => 'GetHostOutput', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ResourceUnavailableException', ], ], ], 'GetRepositoryLink' => [ 'name' => 'GetRepositoryLink', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'GetRepositoryLinkInput', ], 'output' => [ 'shape' => 'GetRepositoryLinkOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ConcurrentModificationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'GetRepositorySyncStatus' => [ 'name' => 'GetRepositorySyncStatus', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'GetRepositorySyncStatusInput', ], 'output' => [ 'shape' => 'GetRepositorySyncStatusOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'GetResourceSyncStatus' => [ 'name' => 'GetResourceSyncStatus', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'GetResourceSyncStatusInput', ], 'output' => [ 'shape' => 'GetResourceSyncStatusOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'GetSyncBlockerSummary' => [ 'name' => 'GetSyncBlockerSummary', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'GetSyncBlockerSummaryInput', ], 'output' => [ 'shape' => 'GetSyncBlockerSummaryOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'GetSyncConfiguration' => [ 'name' => 'GetSyncConfiguration', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'GetSyncConfigurationInput', ], 'output' => [ 'shape' => 'GetSyncConfigurationOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'ListConnections' => [ 'name' => 'ListConnections', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ListConnectionsInput', ], 'output' => [ 'shape' => 'ListConnectionsOutput', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], ], ], 'ListHosts' => [ 'name' => 'ListHosts', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ListHostsInput', ], 'output' => [ 'shape' => 'ListHostsOutput', ], ], 'ListRepositoryLinks' => [ 'name' => 'ListRepositoryLinks', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ListRepositoryLinksInput', ], 'output' => [ 'shape' => 'ListRepositoryLinksOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ConcurrentModificationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'ListRepositorySyncDefinitions' => [ 'name' => 'ListRepositorySyncDefinitions', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ListRepositorySyncDefinitionsInput', ], 'output' => [ 'shape' => 'ListRepositorySyncDefinitionsOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'ListSyncConfigurations' => [ 'name' => 'ListSyncConfigurations', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ListSyncConfigurationsInput', ], 'output' => [ 'shape' => 'ListSyncConfigurationsOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'ListTagsForResource' => [ 'name' => 'ListTagsForResource', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ListTagsForResourceInput', ], 'output' => [ 'shape' => 'ListTagsForResourceOutput', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], ], ], 'TagResource' => [ 'name' => 'TagResource', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'TagResourceInput', ], 'output' => [ 'shape' => 'TagResourceOutput', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'LimitExceededException', ], ], ], 'UntagResource' => [ 'name' => 'UntagResource', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'UntagResourceInput', ], 'output' => [ 'shape' => 'UntagResourceOutput', ], 'errors' => [ [ 'shape' => 'ResourceNotFoundException', ], ], ], 'UpdateHost' => [ 'name' => 'UpdateHost', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'UpdateHostInput', ], 'output' => [ 'shape' => 'UpdateHostOutput', ], 'errors' => [ [ 'shape' => 'ConflictException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ResourceUnavailableException', ], [ 'shape' => 'UnsupportedOperationException', ], ], ], 'UpdateRepositoryLink' => [ 'name' => 'UpdateRepositoryLink', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'UpdateRepositoryLinkInput', ], 'output' => [ 'shape' => 'UpdateRepositoryLinkOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ConditionalCheckFailedException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ThrottlingException', ], [ 'shape' => 'UpdateOutOfSyncException', ], ], ], 'UpdateSyncBlocker' => [ 'name' => 'UpdateSyncBlocker', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'UpdateSyncBlockerInput', ], 'output' => [ 'shape' => 'UpdateSyncBlockerOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'RetryLatestCommitFailedException', ], [ 'shape' => 'SyncBlockerDoesNotExistException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'UpdateSyncConfiguration' => [ 'name' => 'UpdateSyncConfiguration', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'UpdateSyncConfigurationInput', ], 'output' => [ 'shape' => 'UpdateSyncConfigurationOutput', ], 'errors' => [ [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ConcurrentModificationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'InvalidInputException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ThrottlingException', ], [ 'shape' => 'UpdateOutOfSyncException', ], ], ], ], 'shapes' => [ 'AccessDeniedException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'AccountId' => [ 'type' => 'string', 'max' => 12, 'min' => 12, 'pattern' => '[0-9]{12}', ], 'AmazonResourceName' => [ 'type' => 'string', 'max' => 1011, 'min' => 1, 'pattern' => 'arn:aws(-[\\w]+)*:.+:.+:[0-9]{12}:.+', ], 'BlockerStatus' => [ 'type' => 'string', 'enum' => [ 'ACTIVE', 'RESOLVED', ], ], 'BlockerType' => [ 'type' => 'string', 'enum' => [ 'AUTOMATED', ], ], 'BranchName' => [ 'type' => 'string', 'max' => 255, 'min' => 1, 'pattern' => '^.*$', ], 'ConcurrentModificationException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'ConditionalCheckFailedException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'ConflictException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'Connection' => [ 'type' => 'structure', 'members' => [ 'ConnectionName' => [ 'shape' => 'ConnectionName', ], 'ConnectionArn' => [ 'shape' => 'ConnectionArn', ], 'ProviderType' => [ 'shape' => 'ProviderType', ], 'OwnerAccountId' => [ 'shape' => 'AccountId', ], 'ConnectionStatus' => [ 'shape' => 'ConnectionStatus', ], 'HostArn' => [ 'shape' => 'HostArn', ], ], ], 'ConnectionArn' => [ 'type' => 'string', 'max' => 256, 'min' => 0, 'pattern' => 'arn:aws(-[\\w]+)*:.+:.+:[0-9]{12}:.+', ], 'ConnectionList' => [ 'type' => 'list', 'member' => [ 'shape' => 'Connection', ], ], 'ConnectionName' => [ 'type' => 'string', 'max' => 32, 'min' => 1, 'pattern' => '[\\s\\S]*', ], 'ConnectionStatus' => [ 'type' => 'string', 'enum' => [ 'PENDING', 'AVAILABLE', 'ERROR', ], ], 'CreateConnectionInput' => [ 'type' => 'structure', 'required' => [ 'ConnectionName', ], 'members' => [ 'ProviderType' => [ 'shape' => 'ProviderType', ], 'ConnectionName' => [ 'shape' => 'ConnectionName', ], 'Tags' => [ 'shape' => 'TagList', ], 'HostArn' => [ 'shape' => 'HostArn', ], ], ], 'CreateConnectionOutput' => [ 'type' => 'structure', 'required' => [ 'ConnectionArn', ], 'members' => [ 'ConnectionArn' => [ 'shape' => 'ConnectionArn', ], 'Tags' => [ 'shape' => 'TagList', ], ], ], 'CreateHostInput' => [ 'type' => 'structure', 'required' => [ 'Name', 'ProviderType', 'ProviderEndpoint', ], 'members' => [ 'Name' => [ 'shape' => 'HostName', ], 'ProviderType' => [ 'shape' => 'ProviderType', ], 'ProviderEndpoint' => [ 'shape' => 'Url', ], 'VpcConfiguration' => [ 'shape' => 'VpcConfiguration', ], 'Tags' => [ 'shape' => 'TagList', ], ], ], 'CreateHostOutput' => [ 'type' => 'structure', 'members' => [ 'HostArn' => [ 'shape' => 'HostArn', ], 'Tags' => [ 'shape' => 'TagList', ], ], ], 'CreateRepositoryLinkInput' => [ 'type' => 'structure', 'required' => [ 'ConnectionArn', 'OwnerId', 'RepositoryName', ], 'members' => [ 'ConnectionArn' => [ 'shape' => 'ConnectionArn', ], 'OwnerId' => [ 'shape' => 'OwnerId', ], 'RepositoryName' => [ 'shape' => 'RepositoryName', ], 'EncryptionKeyArn' => [ 'shape' => 'KmsKeyArn', ], 'Tags' => [ 'shape' => 'TagList', ], ], ], 'CreateRepositoryLinkOutput' => [ 'type' => 'structure', 'required' => [ 'RepositoryLinkInfo', ], 'members' => [ 'RepositoryLinkInfo' => [ 'shape' => 'RepositoryLinkInfo', ], ], ], 'CreateSyncConfigurationInput' => [ 'type' => 'structure', 'required' => [ 'Branch', 'ConfigFile', 'RepositoryLinkId', 'ResourceName', 'RoleArn', 'SyncType', ], 'members' => [ 'Branch' => [ 'shape' => 'BranchName', ], 'ConfigFile' => [ 'shape' => 'DeploymentFilePath', ], 'RepositoryLinkId' => [ 'shape' => 'RepositoryLinkId', ], 'ResourceName' => [ 'shape' => 'ResourceName', ], 'RoleArn' => [ 'shape' => 'IamRoleArn', ], 'SyncType' => [ 'shape' => 'SyncConfigurationType', ], ], ], 'CreateSyncConfigurationOutput' => [ 'type' => 'structure', 'required' => [ 'SyncConfiguration', ], 'members' => [ 'SyncConfiguration' => [ 'shape' => 'SyncConfiguration', ], ], ], 'CreatedReason' => [ 'type' => 'string', ], 'DeleteConnectionInput' => [ 'type' => 'structure', 'required' => [ 'ConnectionArn', ], 'members' => [ 'ConnectionArn' => [ 'shape' => 'ConnectionArn', ], ], ], 'DeleteConnectionOutput' => [ 'type' => 'structure', 'members' => [], ], 'DeleteHostInput' => [ 'type' => 'structure', 'required' => [ 'HostArn', ], 'members' => [ 'HostArn' => [ 'shape' => 'HostArn', ], ], ], 'DeleteHostOutput' => [ 'type' => 'structure', 'members' => [], ], 'DeleteRepositoryLinkInput' => [ 'type' => 'structure', 'required' => [ 'RepositoryLinkId', ], 'members' => [ 'RepositoryLinkId' => [ 'shape' => 'RepositoryLinkId', ], ], ], 'DeleteRepositoryLinkOutput' => [ 'type' => 'structure', 'members' => [], ], 'DeleteSyncConfigurationInput' => [ 'type' => 'structure', 'required' => [ 'SyncType', 'ResourceName', ], 'members' => [ 'SyncType' => [ 'shape' => 'SyncConfigurationType', ], 'ResourceName' => [ 'shape' => 'ResourceName', ], ], ], 'DeleteSyncConfigurationOutput' => [ 'type' => 'structure', 'members' => [], ], 'DeploymentFilePath' => [ 'type' => 'string', ], 'Directory' => [ 'type' => 'string', ], 'ErrorMessage' => [ 'type' => 'string', 'max' => 600, ], 'Event' => [ 'type' => 'string', ], 'ExternalId' => [ 'type' => 'string', ], 'GetConnectionInput' => [ 'type' => 'structure', 'required' => [ 'ConnectionArn', ], 'members' => [ 'ConnectionArn' => [ 'shape' => 'ConnectionArn', ], ], ], 'GetConnectionOutput' => [ 'type' => 'structure', 'members' => [ 'Connection' => [ 'shape' => 'Connection', ], ], ], 'GetHostInput' => [ 'type' => 'structure', 'required' => [ 'HostArn', ], 'members' => [ 'HostArn' => [ 'shape' => 'HostArn', ], ], ], 'GetHostOutput' => [ 'type' => 'structure', 'members' => [ 'Name' => [ 'shape' => 'HostName', ], 'Status' => [ 'shape' => 'HostStatus', ], 'ProviderType' => [ 'shape' => 'ProviderType', ], 'ProviderEndpoint' => [ 'shape' => 'Url', ], 'VpcConfiguration' => [ 'shape' => 'VpcConfiguration', ], ], ], 'GetRepositoryLinkInput' => [ 'type' => 'structure', 'required' => [ 'RepositoryLinkId', ], 'members' => [ 'RepositoryLinkId' => [ 'shape' => 'RepositoryLinkId', ], ], ], 'GetRepositoryLinkOutput' => [ 'type' => 'structure', 'required' => [ 'RepositoryLinkInfo', ], 'members' => [ 'RepositoryLinkInfo' => [ 'shape' => 'RepositoryLinkInfo', ], ], ], 'GetRepositorySyncStatusInput' => [ 'type' => 'structure', 'required' => [ 'Branch', 'RepositoryLinkId', 'SyncType', ], 'members' => [ 'Branch' => [ 'shape' => 'BranchName', ], 'RepositoryLinkId' => [ 'shape' => 'RepositoryLinkId', ], 'SyncType' => [ 'shape' => 'SyncConfigurationType', ], ], ], 'GetRepositorySyncStatusOutput' => [ 'type' => 'structure', 'required' => [ 'LatestSync', ], 'members' => [ 'LatestSync' => [ 'shape' => 'RepositorySyncAttempt', ], ], ], 'GetResourceSyncStatusInput' => [ 'type' => 'structure', 'required' => [ 'ResourceName', 'SyncType', ], 'members' => [ 'ResourceName' => [ 'shape' => 'ResourceName', ], 'SyncType' => [ 'shape' => 'SyncConfigurationType', ], ], ], 'GetResourceSyncStatusOutput' => [ 'type' => 'structure', 'required' => [ 'LatestSync', ], 'members' => [ 'DesiredState' => [ 'shape' => 'Revision', ], 'LatestSuccessfulSync' => [ 'shape' => 'ResourceSyncAttempt', ], 'LatestSync' => [ 'shape' => 'ResourceSyncAttempt', ], ], ], 'GetSyncBlockerSummaryInput' => [ 'type' => 'structure', 'required' => [ 'SyncType', 'ResourceName', ], 'members' => [ 'SyncType' => [ 'shape' => 'SyncConfigurationType', ], 'ResourceName' => [ 'shape' => 'ResourceName', ], ], ], 'GetSyncBlockerSummaryOutput' => [ 'type' => 'structure', 'required' => [ 'SyncBlockerSummary', ], 'members' => [ 'SyncBlockerSummary' => [ 'shape' => 'SyncBlockerSummary', ], ], ], 'GetSyncConfigurationInput' => [ 'type' => 'structure', 'required' => [ 'SyncType', 'ResourceName', ], 'members' => [ 'SyncType' => [ 'shape' => 'SyncConfigurationType', ], 'ResourceName' => [ 'shape' => 'ResourceName', ], ], ], 'GetSyncConfigurationOutput' => [ 'type' => 'structure', 'required' => [ 'SyncConfiguration', ], 'members' => [ 'SyncConfiguration' => [ 'shape' => 'SyncConfiguration', ], ], ], 'Host' => [ 'type' => 'structure', 'members' => [ 'Name' => [ 'shape' => 'HostName', ], 'HostArn' => [ 'shape' => 'HostArn', ], 'ProviderType' => [ 'shape' => 'ProviderType', ], 'ProviderEndpoint' => [ 'shape' => 'Url', ], 'VpcConfiguration' => [ 'shape' => 'VpcConfiguration', ], 'Status' => [ 'shape' => 'HostStatus', ], 'StatusMessage' => [ 'shape' => 'HostStatusMessage', ], ], ], 'HostArn' => [ 'type' => 'string', 'max' => 256, 'min' => 0, 'pattern' => 'arn:aws(-[\\w]+)*:codestar-connections:.+:[0-9]{12}:host\\/.+', ], 'HostList' => [ 'type' => 'list', 'member' => [ 'shape' => 'Host', ], ], 'HostName' => [ 'type' => 'string', 'max' => 64, 'min' => 1, 'pattern' => '.*', ], 'HostStatus' => [ 'type' => 'string', 'max' => 64, 'min' => 1, 'pattern' => '.*', ], 'HostStatusMessage' => [ 'type' => 'string', ], 'IamRoleArn' => [ 'type' => 'string', 'max' => 1024, 'min' => 1, 'pattern' => 'arn:aws(-[\\w]+)*:iam::\\d{12}:role/[a-zA-Z_0-9+=,.@\\-_/]+', ], 'Id' => [ 'type' => 'string', 'max' => 50, 'min' => 1, ], 'InternalServerException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'InvalidInputException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'KmsKeyArn' => [ 'type' => 'string', 'max' => 1024, 'min' => 1, 'pattern' => 'arn:aws(-[\\w]+)*:kms:[a-z\\-0-9]+:\\d{12}:key/[a-zA-Z0-9\\-]+', ], 'LatestSyncBlockerList' => [ 'type' => 'list', 'member' => [ 'shape' => 'SyncBlocker', ], ], 'LimitExceededException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'ListConnectionsInput' => [ 'type' => 'structure', 'members' => [ 'ProviderTypeFilter' => [ 'shape' => 'ProviderType', ], 'HostArnFilter' => [ 'shape' => 'HostArn', ], 'MaxResults' => [ 'shape' => 'MaxResults', ], 'NextToken' => [ 'shape' => 'NextToken', ], ], ], 'ListConnectionsOutput' => [ 'type' => 'structure', 'members' => [ 'Connections' => [ 'shape' => 'ConnectionList', ], 'NextToken' => [ 'shape' => 'NextToken', ], ], ], 'ListHostsInput' => [ 'type' => 'structure', 'members' => [ 'MaxResults' => [ 'shape' => 'MaxResults', ], 'NextToken' => [ 'shape' => 'NextToken', ], ], ], 'ListHostsOutput' => [ 'type' => 'structure', 'members' => [ 'Hosts' => [ 'shape' => 'HostList', ], 'NextToken' => [ 'shape' => 'NextToken', ], ], ], 'ListRepositoryLinksInput' => [ 'type' => 'structure', 'members' => [ 'MaxResults' => [ 'shape' => 'MaxResults', ], 'NextToken' => [ 'shape' => 'SharpNextToken', ], ], ], 'ListRepositoryLinksOutput' => [ 'type' => 'structure', 'required' => [ 'RepositoryLinks', ], 'members' => [ 'RepositoryLinks' => [ 'shape' => 'RepositoryLinkList', ], 'NextToken' => [ 'shape' => 'SharpNextToken', ], ], ], 'ListRepositorySyncDefinitionsInput' => [ 'type' => 'structure', 'required' => [ 'RepositoryLinkId', 'SyncType', ], 'members' => [ 'RepositoryLinkId' => [ 'shape' => 'RepositoryLinkId', ], 'SyncType' => [ 'shape' => 'SyncConfigurationType', ], ], ], 'ListRepositorySyncDefinitionsOutput' => [ 'type' => 'structure', 'required' => [ 'RepositorySyncDefinitions', ], 'members' => [ 'RepositorySyncDefinitions' => [ 'shape' => 'RepositorySyncDefinitionList', ], 'NextToken' => [ 'shape' => 'SharpNextToken', ], ], ], 'ListSyncConfigurationsInput' => [ 'type' => 'structure', 'required' => [ 'RepositoryLinkId', 'SyncType', ], 'members' => [ 'MaxResults' => [ 'shape' => 'MaxResults', ], 'NextToken' => [ 'shape' => 'SharpNextToken', ], 'RepositoryLinkId' => [ 'shape' => 'RepositoryLinkId', ], 'SyncType' => [ 'shape' => 'SyncConfigurationType', ], ], ], 'ListSyncConfigurationsOutput' => [ 'type' => 'structure', 'required' => [ 'SyncConfigurations', ], 'members' => [ 'SyncConfigurations' => [ 'shape' => 'SyncConfigurationList', ], 'NextToken' => [ 'shape' => 'SharpNextToken', ], ], ], 'ListTagsForResourceInput' => [ 'type' => 'structure', 'required' => [ 'ResourceArn', ], 'members' => [ 'ResourceArn' => [ 'shape' => 'AmazonResourceName', ], ], ], 'ListTagsForResourceOutput' => [ 'type' => 'structure', 'members' => [ 'Tags' => [ 'shape' => 'TagList', ], ], ], 'MaxResults' => [ 'type' => 'integer', 'max' => 100, 'min' => 0, ], 'NextToken' => [ 'type' => 'string', 'max' => 1024, 'min' => 1, 'pattern' => '^.*$', ], 'OwnerId' => [ 'type' => 'string', 'max' => 255, 'min' => 1, 'pattern' => '^.*$', ], 'Parent' => [ 'type' => 'string', ], 'ProviderType' => [ 'type' => 'string', 'enum' => [ 'Bitbucket', 'GitHub', 'GitHubEnterpriseServer', 'GitLab', 'GitLabSelfManaged', ], ], 'RepositoryLinkArn' => [ 'type' => 'string', 'pattern' => '^arn:aws(?:-[a-z]+)*:codestar-connections:[a-z\\-0-9]+:\\d{12}:repository-link\\/[a-zA-Z0-9\\-:/]+', ], 'RepositoryLinkId' => [ 'type' => 'string', 'pattern' => '^[0-9a-fA-F]{8}\\b-[0-9a-fA-F]{4}\\b-[0-9a-fA-F]{4}\\b-[0-9a-fA-F]{4}\\b-[0-9a-fA-F]{12}$', ], 'RepositoryLinkInfo' => [ 'type' => 'structure', 'required' => [ 'ConnectionArn', 'OwnerId', 'ProviderType', 'RepositoryLinkArn', 'RepositoryLinkId', 'RepositoryName', ], 'members' => [ 'ConnectionArn' => [ 'shape' => 'ConnectionArn', ], 'EncryptionKeyArn' => [ 'shape' => 'KmsKeyArn', ], 'OwnerId' => [ 'shape' => 'OwnerId', ], 'ProviderType' => [ 'shape' => 'ProviderType', ], 'RepositoryLinkArn' => [ 'shape' => 'RepositoryLinkArn', ], 'RepositoryLinkId' => [ 'shape' => 'RepositoryLinkId', ], 'RepositoryName' => [ 'shape' => 'RepositoryName', ], ], ], 'RepositoryLinkList' => [ 'type' => 'list', 'member' => [ 'shape' => 'RepositoryLinkInfo', ], ], 'RepositoryName' => [ 'type' => 'string', 'max' => 128, 'min' => 1, 'pattern' => '^.*$', ], 'RepositorySyncAttempt' => [ 'type' => 'structure', 'required' => [ 'StartedAt', 'Status', 'Events', ], 'members' => [ 'StartedAt' => [ 'shape' => 'Timestamp', ], 'Status' => [ 'shape' => 'RepositorySyncStatus', ], 'Events' => [ 'shape' => 'RepositorySyncEventList', ], ], ], 'RepositorySyncDefinition' => [ 'type' => 'structure', 'required' => [ 'Branch', 'Directory', 'Parent', 'Target', ], 'members' => [ 'Branch' => [ 'shape' => 'BranchName', ], 'Directory' => [ 'shape' => 'Directory', ], 'Parent' => [ 'shape' => 'Parent', ], 'Target' => [ 'shape' => 'Target', ], ], ], 'RepositorySyncDefinitionList' => [ 'type' => 'list', 'member' => [ 'shape' => 'RepositorySyncDefinition', ], ], 'RepositorySyncEvent' => [ 'type' => 'structure', 'required' => [ 'Event', 'Time', 'Type', ], 'members' => [ 'Event' => [ 'shape' => 'Event', ], 'ExternalId' => [ 'shape' => 'ExternalId', ], 'Time' => [ 'shape' => 'Timestamp', ], 'Type' => [ 'shape' => 'Type', ], ], ], 'RepositorySyncEventList' => [ 'type' => 'list', 'member' => [ 'shape' => 'RepositorySyncEvent', ], ], 'RepositorySyncStatus' => [ 'type' => 'string', 'enum' => [ 'FAILED', 'INITIATED', 'IN_PROGRESS', 'SUCCEEDED', 'QUEUED', ], ], 'ResolvedReason' => [ 'type' => 'string', 'max' => 250, 'min' => 1, ], 'ResourceAlreadyExistsException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'ResourceName' => [ 'type' => 'string', 'max' => 100, 'min' => 1, 'pattern' => '^[0-9A-Za-z]+[0-9A-Za-z_\\\\-]*$', ], 'ResourceNotFoundException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'ResourceSyncAttempt' => [ 'type' => 'structure', 'required' => [ 'Events', 'InitialRevision', 'StartedAt', 'Status', 'TargetRevision', 'Target', ], 'members' => [ 'Events' => [ 'shape' => 'ResourceSyncEventList', ], 'InitialRevision' => [ 'shape' => 'Revision', ], 'StartedAt' => [ 'shape' => 'Timestamp', ], 'Status' => [ 'shape' => 'ResourceSyncStatus', ], 'TargetRevision' => [ 'shape' => 'Revision', ], 'Target' => [ 'shape' => 'Target', ], ], ], 'ResourceSyncEvent' => [ 'type' => 'structure', 'required' => [ 'Event', 'Time', 'Type', ], 'members' => [ 'Event' => [ 'shape' => 'Event', ], 'ExternalId' => [ 'shape' => 'ExternalId', ], 'Time' => [ 'shape' => 'Timestamp', ], 'Type' => [ 'shape' => 'Type', ], ], ], 'ResourceSyncEventList' => [ 'type' => 'list', 'member' => [ 'shape' => 'ResourceSyncEvent', ], ], 'ResourceSyncStatus' => [ 'type' => 'string', 'enum' => [ 'FAILED', 'INITIATED', 'IN_PROGRESS', 'SUCCEEDED', ], ], 'ResourceUnavailableException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'RetryLatestCommitFailedException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'Revision' => [ 'type' => 'structure', 'required' => [ 'Branch', 'Directory', 'OwnerId', 'RepositoryName', 'ProviderType', 'Sha', ], 'members' => [ 'Branch' => [ 'shape' => 'BranchName', ], 'Directory' => [ 'shape' => 'Directory', ], 'OwnerId' => [ 'shape' => 'OwnerId', ], 'RepositoryName' => [ 'shape' => 'RepositoryName', ], 'ProviderType' => [ 'shape' => 'ProviderType', ], 'Sha' => [ 'shape' => 'SHA', ], ], ], 'SHA' => [ 'type' => 'string', 'max' => 255, 'min' => 1, ], 'SecurityGroupId' => [ 'type' => 'string', 'max' => 20, 'min' => 11, 'pattern' => 'sg-\\w{8}(\\w{9})?', ], 'SecurityGroupIds' => [ 'type' => 'list', 'member' => [ 'shape' => 'SecurityGroupId', ], 'max' => 10, 'min' => 1, ], 'SharpNextToken' => [ 'type' => 'string', 'max' => 2048, 'min' => 1, 'pattern' => '^.*$', ], 'SubnetId' => [ 'type' => 'string', 'max' => 24, 'min' => 15, 'pattern' => 'subnet-\\w{8}(\\w{9})?', ], 'SubnetIds' => [ 'type' => 'list', 'member' => [ 'shape' => 'SubnetId', ], 'max' => 10, 'min' => 1, ], 'SyncBlocker' => [ 'type' => 'structure', 'required' => [ 'Id', 'Type', 'Status', 'CreatedReason', 'CreatedAt', ], 'members' => [ 'Id' => [ 'shape' => 'Id', ], 'Type' => [ 'shape' => 'BlockerType', ], 'Status' => [ 'shape' => 'BlockerStatus', ], 'CreatedReason' => [ 'shape' => 'CreatedReason', ], 'CreatedAt' => [ 'shape' => 'Timestamp', ], 'Contexts' => [ 'shape' => 'SyncBlockerContextList', ], 'ResolvedReason' => [ 'shape' => 'ResolvedReason', ], 'ResolvedAt' => [ 'shape' => 'Timestamp', ], ], ], 'SyncBlockerContext' => [ 'type' => 'structure', 'required' => [ 'Key', 'Value', ], 'members' => [ 'Key' => [ 'shape' => 'SyncBlockerContextKey', ], 'Value' => [ 'shape' => 'SyncBlockerContextValue', ], ], ], 'SyncBlockerContextKey' => [ 'type' => 'string', ], 'SyncBlockerContextList' => [ 'type' => 'list', 'member' => [ 'shape' => 'SyncBlockerContext', ], ], 'SyncBlockerContextValue' => [ 'type' => 'string', ], 'SyncBlockerDoesNotExistException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'SyncBlockerSummary' => [ 'type' => 'structure', 'required' => [ 'ResourceName', ], 'members' => [ 'ResourceName' => [ 'shape' => 'ResourceName', ], 'ParentResourceName' => [ 'shape' => 'ResourceName', ], 'LatestBlockers' => [ 'shape' => 'LatestSyncBlockerList', ], ], ], 'SyncConfiguration' => [ 'type' => 'structure', 'required' => [ 'Branch', 'OwnerId', 'ProviderType', 'RepositoryLinkId', 'RepositoryName', 'ResourceName', 'RoleArn', 'SyncType', ], 'members' => [ 'Branch' => [ 'shape' => 'BranchName', ], 'ConfigFile' => [ 'shape' => 'DeploymentFilePath', ], 'OwnerId' => [ 'shape' => 'OwnerId', ], 'ProviderType' => [ 'shape' => 'ProviderType', ], 'RepositoryLinkId' => [ 'shape' => 'RepositoryLinkId', ], 'RepositoryName' => [ 'shape' => 'RepositoryName', ], 'ResourceName' => [ 'shape' => 'ResourceName', ], 'RoleArn' => [ 'shape' => 'IamRoleArn', ], 'SyncType' => [ 'shape' => 'SyncConfigurationType', ], ], ], 'SyncConfigurationList' => [ 'type' => 'list', 'member' => [ 'shape' => 'SyncConfiguration', ], ], 'SyncConfigurationStillExistsException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'SyncConfigurationType' => [ 'type' => 'string', 'enum' => [ 'CFN_STACK_SYNC', ], ], 'Tag' => [ 'type' => 'structure', 'required' => [ 'Key', 'Value', ], 'members' => [ 'Key' => [ 'shape' => 'TagKey', ], 'Value' => [ 'shape' => 'TagValue', ], ], ], 'TagKey' => [ 'type' => 'string', 'max' => 128, 'min' => 1, 'pattern' => '.*', ], 'TagKeyList' => [ 'type' => 'list', 'member' => [ 'shape' => 'TagKey', ], 'max' => 200, 'min' => 0, ], 'TagList' => [ 'type' => 'list', 'member' => [ 'shape' => 'Tag', ], 'max' => 200, 'min' => 0, ], 'TagResourceInput' => [ 'type' => 'structure', 'required' => [ 'ResourceArn', 'Tags', ], 'members' => [ 'ResourceArn' => [ 'shape' => 'AmazonResourceName', ], 'Tags' => [ 'shape' => 'TagList', ], ], ], 'TagResourceOutput' => [ 'type' => 'structure', 'members' => [], ], 'TagValue' => [ 'type' => 'string', 'max' => 256, 'min' => 0, 'pattern' => '.*', ], 'Target' => [ 'type' => 'string', ], 'ThrottlingException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'Timestamp' => [ 'type' => 'timestamp', ], 'TlsCertificate' => [ 'type' => 'string', 'max' => 16384, 'min' => 1, 'pattern' => '[\\s\\S]*', ], 'Type' => [ 'type' => 'string', ], 'UnsupportedOperationException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'UnsupportedProviderTypeException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'UntagResourceInput' => [ 'type' => 'structure', 'required' => [ 'ResourceArn', 'TagKeys', ], 'members' => [ 'ResourceArn' => [ 'shape' => 'AmazonResourceName', ], 'TagKeys' => [ 'shape' => 'TagKeyList', ], ], ], 'UntagResourceOutput' => [ 'type' => 'structure', 'members' => [], ], 'UpdateHostInput' => [ 'type' => 'structure', 'required' => [ 'HostArn', ], 'members' => [ 'HostArn' => [ 'shape' => 'HostArn', ], 'ProviderEndpoint' => [ 'shape' => 'Url', ], 'VpcConfiguration' => [ 'shape' => 'VpcConfiguration', ], ], ], 'UpdateHostOutput' => [ 'type' => 'structure', 'members' => [], ], 'UpdateOutOfSyncException' => [ 'type' => 'structure', 'members' => [ 'Message' => [ 'shape' => 'ErrorMessage', ], ], 'exception' => true, ], 'UpdateRepositoryLinkInput' => [ 'type' => 'structure', 'required' => [ 'RepositoryLinkId', ], 'members' => [ 'ConnectionArn' => [ 'shape' => 'ConnectionArn', ], 'EncryptionKeyArn' => [ 'shape' => 'KmsKeyArn', ], 'RepositoryLinkId' => [ 'shape' => 'RepositoryLinkId', ], ], ], 'UpdateRepositoryLinkOutput' => [ 'type' => 'structure', 'required' => [ 'RepositoryLinkInfo', ], 'members' => [ 'RepositoryLinkInfo' => [ 'shape' => 'RepositoryLinkInfo', ], ], ], 'UpdateSyncBlockerInput' => [ 'type' => 'structure', 'required' => [ 'Id', 'SyncType', 'ResourceName', 'ResolvedReason', ], 'members' => [ 'Id' => [ 'shape' => 'Id', ], 'SyncType' => [ 'shape' => 'SyncConfigurationType', ], 'ResourceName' => [ 'shape' => 'ResourceName', ], 'ResolvedReason' => [ 'shape' => 'ResolvedReason', ], ], ], 'UpdateSyncBlockerOutput' => [ 'type' => 'structure', 'required' => [ 'ResourceName', 'SyncBlocker', ], 'members' => [ 'ResourceName' => [ 'shape' => 'ResourceName', ], 'ParentResourceName' => [ 'shape' => 'ResourceName', ], 'SyncBlocker' => [ 'shape' => 'SyncBlocker', ], ], ], 'UpdateSyncConfigurationInput' => [ 'type' => 'structure', 'required' => [ 'ResourceName', 'SyncType', ], 'members' => [ 'Branch' => [ 'shape' => 'BranchName', ], 'ConfigFile' => [ 'shape' => 'DeploymentFilePath', ], 'RepositoryLinkId' => [ 'shape' => 'RepositoryLinkId', ], 'ResourceName' => [ 'shape' => 'ResourceName', ], 'RoleArn' => [ 'shape' => 'IamRoleArn', ], 'SyncType' => [ 'shape' => 'SyncConfigurationType', ], ], ], 'UpdateSyncConfigurationOutput' => [ 'type' => 'structure', 'required' => [ 'SyncConfiguration', ], 'members' => [ 'SyncConfiguration' => [ 'shape' => 'SyncConfiguration', ], ], ], 'Url' => [ 'type' => 'string', 'max' => 512, 'min' => 1, 'pattern' => '.*', ], 'VpcConfiguration' => [ 'type' => 'structure', 'required' => [ 'VpcId', 'SubnetIds', 'SecurityGroupIds', ], 'members' => [ 'VpcId' => [ 'shape' => 'VpcId', ], 'SubnetIds' => [ 'shape' => 'SubnetIds', ], 'SecurityGroupIds' => [ 'shape' => 'SecurityGroupIds', ], 'TlsCertificate' => [ 'shape' => 'TlsCertificate', ], ], ], 'VpcId' => [ 'type' => 'string', 'max' => 21, 'min' => 12, 'pattern' => 'vpc-\\w{8}(\\w{9})?', ], ],];
