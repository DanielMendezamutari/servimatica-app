import { createMongoAbility } from '@casl/ability'

export const initialAbility = [
  { action: 'read', subject: 'Auth' },
]

export const ability = createMongoAbility(initialAbility)
